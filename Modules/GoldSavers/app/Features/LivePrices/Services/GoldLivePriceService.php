<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use Illuminate\Support\Facades\Cache;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldPriceUpdated;
use Modules\GoldSavers\app\Features\LivePrices\Events\GoldRealtimeBroadcasted;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceEvent;
use Modules\GoldSavers\app\Features\LivePrices\Notifications\GoldStalePriceDetectedNotification;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use App\Models\Tenant;

/**
 * Orchestrates the full price update pipeline:
 * fetch → normalize → validate → snapshot → upsert hot row → broadcast
 */
class GoldLivePriceService
{
    public function __construct(
        protected GoldMarketProviderManager  $providerManager,
        protected GoldPriceAggregator        $aggregator,
        protected GoldPriceSnapshotGenerator $snapshotGenerator,
        protected GoldRealtimeBroadcaster    $broadcaster,
        protected GoldLivePriceLimitsService $limitsService,
    ) {}

    /**
     * Run the full price pipeline for a tenant + market.
     */
    public function fetchAndUpdate(int $tenantId, string $marketKey): GoldLivePrice
    {
        // 1. Fetch from best available provider
        $payload = $this->providerManager->fetchForMarket($tenantId, $marketKey);

        return $this->processPayload($tenantId, $marketKey, $payload);
    }

    /**
     * Process a payload that was already fetched (used by WebSocket drivers).
     */
    public function processPayload(int $tenantId, string $marketKey, GoldPricePayload $payload): GoldLivePrice
    {
        // 2. Load existing live price for validation comparison
        $existing = GoldLivePrice::where('tenant_id', $tenantId)
                        ->where('market_key', $marketKey)
                        ->first();

        // 3. Validate
        $validation = $this->aggregator->validate($payload, $existing, $tenantId);

        if ($validation->isDuplicate) {
            return $existing; // skip — no change
        }

        // 4. Store snapshot (even anomalies — for audit)
        $source = $this->resolveSource($payload);
        $this->snapshotGenerator->generate($tenantId, $payload, $validation, $source);

        // If anomaly: snapshot is stored but live price is NOT updated and NOT broadcast
        if ($validation->isAnomaly) {
            return $existing ?? new GoldLivePrice();
        }

        // 5. Compute delta
        $previousPrice = $existing?->price_gram_24k ?? 0;
        $delta         = round($payload->priceLocalGram24k - $previousPrice, 4);
        $deltaPct      = $previousPrice > 0
            ? round(($delta / $previousPrice) * 100, 4)
            : 0;
        $direction = match (true) {
            $delta > 0  => 'up',
            $delta < 0  => 'down',
            default     => 'flat',
        };

        // 6. Upsert hot live price row
        $livePrice = GoldLivePrice::updateOrCreate(
            ['tenant_id' => $tenantId, 'market_key' => $marketKey],
            [
                'source_id'              => $source?->id,
                'price_usd_oz'           => $payload->priceUsdOz,
                'price_gram_24k'     => $payload->priceLocalGram24k,
                'price_gram_21k'     => $payload->priceLocalGram21k,
                'price_gram_18k'     => $payload->priceLocalGram18k,
                'price_gram_14k'     => $payload->priceLocalGram14k,
                'buy_price'              => $payload->buyPrice,
                'sell_price'             => $payload->sellPrice,
                'spread'                 => round($payload->buyPrice - $payload->sellPrice, 4),
                'currency_id'            => $payload->currencyId,
                'exchange_rate'          => $payload->exchangeRate,
                'price_delta'            => $delta,
                'price_delta_pct'        => $deltaPct,
                'direction'              => $direction,
                'provider_latency_ms'    => $payload->latencyMs,
                'is_stale'               => false,
                'stale_since'            => null,
                'fetched_at'             => now(),
            ]
        );

        // 7. Cache hot row in Redis for 30s
        Cache::put("gold.live.{$tenantId}.{$marketKey}", $livePrice->toArray(), 30);

        // 8. Audit log
        GoldPriceEvent::logEvent($tenantId, 'price_updated', [
            'market_key' => $marketKey,
            'price'      => $payload->priceLocalGram24k,
            'delta'      => $delta,
            'direction'  => $direction,
        ]);

        // 9. Broadcast via WebSocket
        $this->broadcaster->broadcast($tenantId, $livePrice);

        return $livePrice;
    }

    /**
     * Check staleness for a market and mark/notify if stale.
     */
    public function checkStaleness(int $tenantId, string $marketKey): void
    {
        $livePrice = GoldLivePrice::where('tenant_id', $tenantId)
                        ->where('market_key', $marketKey)
                        ->first();

        if ($this->aggregator->isStale($livePrice)) {
            $livePrice?->markStale();

            GoldPriceEvent::logEvent($tenantId, 'stale_detected', [
                'market_key' => $marketKey,
                'last_fetch' => $livePrice?->fetched_at,
            ], 'warning');

            // Notify tenant admin
            $tenant = Tenant::find($tenantId);
            if ($tenant && $tenant->adminUser) {
                $tenant->adminUser->notify(new GoldStalePriceDetectedNotification($marketKey, $livePrice));
            }
        }
    }

    /**
     * Get the current live price from cache or DB.
     */
    public function getCachedLivePrice(int $tenantId, string $marketKey): ?GoldLivePrice
    {
        $cached = Cache::get("gold.live.{$tenantId}.{$marketKey}");

        if ($cached) {
            return (new GoldLivePrice())->forceFill($cached);
        }

        return GoldLivePrice::where('tenant_id', $tenantId)
                    ->where('market_key', $marketKey)
                    ->first();
    }

    protected function resolveSource(GoldPricePayload $payload): ?GoldMarketSource
    {
        return GoldMarketSource::where('market_key', $payload->marketKey)
                    ->where('is_active', true)
                    ->orderBy('priority')
                    ->first();
    }
}
