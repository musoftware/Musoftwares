<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use Modules\GoldSavers\app\Features\LivePrices\Events\GoldHistoricalSnapshotGenerated;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceEvent;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceSnapshot;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;

/**
 * Persists validated price payloads as immutable snapshots.
 * Handles deduplication and anomaly flagging at storage time.
 */
class GoldPriceSnapshotGenerator
{
    /**
     * Generate and store a snapshot for a validated payload.
     */
    public function generate(
        int               $tenantId,
        GoldPricePayload  $payload,
        ValidationResult  $validation,
        ?GoldMarketSource $source,
        string            $interval = 'minute',
    ): GoldPriceSnapshot {
        $snapshot = GoldPriceSnapshot::create([
            'tenant_id'         => $tenantId,
            'source_id'         => $source?->id,
            'market_key'        => $payload->marketKey,
            'price_usd_oz'      => $payload->priceUsdOz,
            'price_gram_24k' => $payload->priceLocalGram24k,
            'price_gram_21k' => $payload->priceLocalGram21k,
            'price_gram_18k' => $payload->priceLocalGram18k,
            'price_gram_14k' => $payload->priceLocalGram14k,
            'buy_price'         => $payload->buyPrice,
            'sell_price'        => $payload->sellPrice,
            'currency_id'       => $payload->currencyId,
            'exchange_rate'     => $payload->exchangeRate,
            'validation_passed' => $validation->passed,
            'anomaly_detected'  => $validation->isAnomaly,
            'anomaly_reason'    => $validation->failureReason,
            'raw_payload'       => $payload->rawPayload,
            'interval'          => $interval,
            'fetched_at'        => now(),
        ]);

        // Audit log in price events
        GoldPriceEvent::logEvent(
            $tenantId,
            'snapshot_generated',
            ['market_key' => $payload->marketKey, 'snapshot_id' => $snapshot->id],
            'info',
            $source?->id,
        );

        event(new GoldHistoricalSnapshotGenerated($tenantId, $snapshot));

        return $snapshot;
    }
}
