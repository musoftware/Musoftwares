<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Providers\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\GoldSavers\app\Features\LivePrices\Exceptions\GoldProviderException;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldPricePayload;
use Modules\GoldSavers\app\Features\LivePrices\Providers\GoldProviderDriver;

/**
 * HTTP API driver — polls a REST endpoint for the current gold price.
 * Compatible with MetalPriceAPI, GoldAPI.io, and custom REST endpoints.
 */
class GoldApiDriver implements GoldProviderDriver
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

        $start = microtime(true);

        $response = Http::withHeaders([
            'X-API-KEY'     => $apiKey,
            'Authorization' => "Bearer {$apiKey}",
            'Accept'        => 'application/json',
        ])->timeout(10)->get($url);

        $this->latencyMs = (int) ((microtime(true) - $start) * 1000);

        if (!$response->successful()) {
            throw new GoldProviderException(
                "Provider [{$this->source->name}] returned HTTP {$response->status()}",
                $this->source
            );
        }

        $data = $response->json();

        return $this->normalize($data);
    }

    public function isHealthy(): bool
    {
        try {
            $url = $this->source->endpoint_url;
            $response = Http::timeout(5)->head($url);
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
        return 'api';
    }

    public function getLatencyMs(): int
    {
        return $this->latencyMs;
    }

    /**
     * Normalize the raw API response into a standard GoldPricePayload.
     * Supports multiple API response formats via flexible key mapping.
     */
    protected function normalize(array $data): GoldPricePayload
    {
        $currencyCode  = $this->source->base_currency ?? 'EGP';
        $currencyId    = \App\Models\Currency::where('code', $currencyCode)->first()?->id ?? 2;
        $exchangeRate  = (float) ($credentials['exchange_rate'] ?? 1.0);

        // Support multiple response structures (GoldAPI, MetalPriceAPI, custom)
        $priceUsdOz = (float) (
            $data['price']             ??
            $data['price_oz']          ??
            $data['rates']['XAU']      ??
            $data['metal_price']       ??
            0
        );

        // Convert USD/oz to local gram price: 1 troy oz = 31.1035 grams
        $priceLocalGram24k = $priceUsdOz > 0
            ? round(($priceUsdOz / 31.1035) * $exchangeRate, 4)
            : (float) ($data['price_gram_24k'] ?? $data['gram_24k'] ?? 0);

        return GoldPricePayload::fromBase24k(
            marketKey:       $this->source->market_key,
            priceUsdOz:      $priceUsdOz,
            price24kPerGram: $priceLocalGram24k,
            currencyId:      $currencyId,
            exchangeRate:    $exchangeRate,
            latencyMs:       $this->latencyMs,
            rawPayload:      $data,
        );
    }
}
