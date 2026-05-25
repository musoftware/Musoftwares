<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use Modules\GoldSavers\app\Features\LivePrices\Events\GoldPriceAnomalyDetected;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;

/**
 * Validates incoming price payloads against known-good data.
 *
 * Protects against: spikes, stale prices, duplicates, malformed data.
 */
class GoldPriceAggregator
{
    /** Minimum acceptable 24K gram price in EGP */
    protected float $minPrice = 10.0;

    /** Maximum acceptable 24K gram price in EGP */
    protected float $maxPrice = 100_000.0;

    /** Minutes before a non-updating price is considered stale */
    protected int $staleAfterMinutes = 30;

    public function validate(GoldPricePayload $payload, ?GoldLivePrice $existing, int $tenantId): ValidationResult
    {
        // 1. Basic sanity checks
        if ($payload->priceLocalGram24k <= 0) {
            return ValidationResult::invalid('price_zero', 'Price is zero or negative');
        }

        if ($payload->priceLocalGram24k < $this->minPrice || $payload->priceLocalGram24k > $this->maxPrice) {
            return ValidationResult::anomaly('price_out_of_range', "Price {$payload->priceLocalGram24k} outside acceptable range [{$this->minPrice}, {$this->maxPrice}]");
        }

        // 2. Spike detection against previous price
        if ($existing && $existing->price_gram_24k > 0) {
            $changePct = abs(
                ($payload->priceLocalGram24k - $existing->price_gram_24k) / $existing->price_gram_24k
            ) * 100;

            // Use per-source threshold if available
            $threshold = 15.0; // default 15% spike threshold

            if ($changePct > $threshold) {
                $anomaly = ValidationResult::anomaly(
                    'spike_detected',
                    "Price changed {$changePct}% (threshold: {$threshold}%) — possible data spike"
                );

                event(new GoldPriceAnomalyDetected($tenantId, $payload, $changePct, 'spike_detected'));

                return $anomaly;
            }
        }

        // 3. Duplicate check — skip if price unchanged within 30 seconds
        if ($existing && $existing->price_gram_24k == $payload->priceLocalGram24k) {
            $lastFetch = $existing->fetched_at;
            if ($lastFetch && now()->diffInSeconds($lastFetch) < 30) {
                return ValidationResult::duplicate();
            }
        }

        // 4. Currency/exchange rate sanity
        if ($payload->exchangeRate <= 0) {
            return ValidationResult::invalid('invalid_exchange_rate', 'Exchange rate is zero or negative');
        }

        return ValidationResult::valid();
    }

    /**
     * Check if the existing live price is stale.
     */
    public function isStale(?GoldLivePrice $livePrice): bool
    {
        if (!$livePrice || !$livePrice->fetched_at) return true;
        return now()->diffInMinutes($livePrice->fetched_at) >= $this->staleAfterMinutes;
    }
}
