<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;

class GoldLivePriceFactory extends Factory
{
    protected $model = GoldLivePrice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => 1,
            'source_id' => null,
            'market_key' => 'egypt_local',
            'price_usd_oz' => 2000.00,
            'price_gram_24k' => 4000.00,
            'price_gram_21k' => 3500.00,
            'price_gram_18k' => 3000.00,
            'price_gram_14k' => 2333.33,
            'buy_price' => 3950.00,
            'sell_price' => 4050.00,
            'spread' => 100.00,
            'currency_id' => 1,
            'exchange_rate' => 50.00,
            'direction' => 'up',
            'fetched_at' => now(),
        ];
    }
}
