<?php

namespace Modules\GoldSavers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use App\Models\Tenant;
use App\Models\Currency;

class GoldLivePriceFactory extends Factory
{
    protected $model = GoldLivePrice::class;

    public function definition()
    {
        $price24k = $this->faker->randomFloat(4, 3000, 4000);

        return [
            'tenant_id' => Tenant::factory(),
            'source_id' => GoldMarketSource::factory(),
            'market_key' => $this->faker->unique()->word . '_market',
            'price_usd_oz' => $this->faker->randomFloat(4, 1800, 2500),
            'price_gram_24k' => $price24k,
            'price_gram_21k' => $price24k * (21 / 24),
            'price_gram_18k' => $price24k * (18 / 24),
            'price_gram_14k' => $price24k * (14 / 24),
            'buy_price' => $price24k * 1.02,
            'sell_price' => $price24k * 0.98,
            'spread' => ($price24k * 1.02) - ($price24k * 0.98),
            'currency_id' => Currency::inRandomOrder()->first()->id ?? 1,
            'exchange_rate' => 1.0,
            'price_delta' => 0,
            'price_delta_pct' => 0,
            'direction' => 'flat',
            'provider_latency_ms' => 150,
            'is_stale' => false,
            'fetched_at' => now(),
            'broadcasted_at' => now(),
        ];
    }
}
