<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Providers;

/**
 * Standard Data Transfer Object returned by every provider driver.
 */
final class GoldPricePayload
{
    public function __construct(
        public readonly string $marketKey,
        public readonly float  $priceUsdOz,
        public readonly float  $priceLocalGram24k,
        public readonly float  $priceLocalGram21k,
        public readonly float  $priceLocalGram18k,
        public readonly float  $priceLocalGram14k,
        public readonly float  $buyPrice,
        public readonly float  $sellPrice,
        public readonly int    $currencyId,
        public readonly float  $exchangeRate,
        public readonly int    $latencyMs,
        public readonly array  $rawPayload = [],
    ) {}

    /**
     * Derive karat price from 24K base using standard purity ratios.
     */
    public static function fromBase24k(
        string $marketKey,
        float  $priceUsdOz,
        float  $price24kPerGram,
        int    $currencyId,
        float  $exchangeRate,
        int    $latencyMs,
        array  $rawPayload = [],
    ): self {
        $buyMultiplier  = 1.02;
        $sellMultiplier = 0.98;

        return new self(
            marketKey:         $marketKey,
            priceUsdOz:        $priceUsdOz,
            priceLocalGram24k: round($price24kPerGram, 4),
            priceLocalGram21k: round($price24kPerGram * (21 / 24), 4),
            priceLocalGram18k: round($price24kPerGram * (18 / 24), 4),
            priceLocalGram14k: round($price24kPerGram * (14 / 24), 4),
            buyPrice:          round($price24kPerGram * $buyMultiplier, 4),
            sellPrice:         round($price24kPerGram * $sellMultiplier, 4),
            currencyId:        $currencyId,
            exchangeRate:      $exchangeRate,
            latencyMs:         $latencyMs,
            rawPayload:        $rawPayload,
        );
    }
}
