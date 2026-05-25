<?php

namespace Modules\GoldSavers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;

class GoldMarketSourceFactory extends Factory
{
    protected $model = GoldMarketSource::class;

    public function definition()
    {
        return [
            'name' => 'Test Market',
            'market_key' => $this->faker->unique()->word . '_market',
            'driver' => 'manual',
            'config' => [],
            'is_active' => true,
            'priority' => 1,
        ];
    }
}
