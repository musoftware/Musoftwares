<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\GoldSavers\app\Features\LivePrices\Exceptions\GoldProviderException;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldProviderDriver;

/**
 * Vendor price driver — fetches prices from custom vendor/supplier REST endpoints.
 * Useful for gold shop integrations with their own pricing APIs.
 */
class GoldVendorDriver implements GoldProviderDriver
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
        $url         = $this->source->endpoint_url;
        $apiKey      = $credentials['api_key'] ?? null;

        $headers = array_filter([
            'Accept'        => 'application/json',
            'Authorization' => $apiKey ? "Bearer {$apiKey}" : null,
            'X-Vendor-Key'  => $credentials['vendor_key'] ?? null,
        ]);

        $start = microtime(true);

        $response = Http::withHeaders($headers)->timeout(10)->get($url);

        $this->latencyMs = (int) ((microtime(true) - $start) * 1000);

        if (!$response->successful()) {
            throw new GoldProviderException(
                "Vendor [{$this->source->name}] returned HTTP {$response->status()}",
                $this->source
            );
        }

        $data = $response->json();

        // Vendor responses use configurable key mapping
        $keyMap        = $credentials['key_map'] ?? [];
        $currencyCode  = $this->source->base_currency ?? null;
        $currencyId    = \App\Models\Currency::where('code', $currencyCode)->first()?->id ?? 2;
        $exchangeRate  = (float) ($credentials['exchange_rate'] ?? 1.0);

        $price24k = (float) (
            $data[$keyMap['price_24k'] ?? 'price_24k'] ??
            $data['karat_24'] ??
            $data['gold_24k'] ??
            0
        );

        return GoldPricePayload::fromBase24k(
            marketKey:       $this->source->market_key,
            priceUsdOz:      0,
            price24kPerGram: $price24k,
            currencyId:      $currencyId,
            exchangeRate:    $exchangeRate,
            latencyMs:       $this->latencyMs,
            rawPayload:      $data,
        );
    }

    public function isHealthy(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->source->endpoint_url);
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function getMarketKey(): string
    {
        return $this->source->market_key;
    }

    public function getDriverType(): string
    {
        return 'vendor';
    }

    public function getLatencyMs(): int
    {
        return $this->latencyMs;
    }
}

