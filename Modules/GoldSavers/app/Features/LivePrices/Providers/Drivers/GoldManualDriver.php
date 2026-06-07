<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers;

use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldProviderDriver;

/**
 * Manual price driver — uses admin-entered override prices.
 * Acts as the final failover when all other providers are unavailable.
 */
class GoldManualDriver implements GoldProviderDriver
{
    protected GoldMarketSource $source;
    protected int $latencyMs = 0;

    public function configure(GoldMarketSource $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function fetch(): GoldPricePayload
    {
        $credentials = $this->source->credentials ?? [];

        $priceLocalGram24k = (float) ($credentials['price_gram_24k'] ?? 0);
        $priceUsdOz        = (float) ($credentials['price_usd_oz']   ?? 0);
        $currencyCode      = $this->source->base_currency ?? null;
        $currencyId        = \App\Models\Currency::where('code', $currencyCode)->first()?->id ?? 2; // Default to 2 for EGP
        $exchangeRate      = (float) ($credentials['exchange_rate']   ?? 1.0);

        return GoldPricePayload::fromBase24k(
            marketKey:       $this->source->market_key,
            priceUsdOz:      $priceUsdOz,
            price24kPerGram: $priceLocalGram24k,
            currencyId:      $currencyId,
            exchangeRate:    $exchangeRate,
            latencyMs:       0,
            rawPayload:      ['source' => 'manual_override', 'credentials' => array_except($credentials, ['api_key'])],
        );
    }

    public function isHealthy(): bool
    {
        // Manual driver is always healthy as long as price data exists
        $credentials = $this->source->credentials ?? [];
        return isset($credentials['price_gram_24k']) && $credentials['price_gram_24k'] > 0;
    }

    public function getMarketKey(): string
    {
        return $this->source->market_key;
    }

    public function getDriverType(): string
    {
        return 'manual';
    }

    public function getLatencyMs(): int
    {
        return $this->latencyMs;
    }
}

