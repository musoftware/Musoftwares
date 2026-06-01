<?php

namespace Database\Factories;

use App\Models\Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoldMarketSource>
 */
class GoldMarketSourceFactory extends Factory
{
    protected $model = \Modules\GoldSavers\app\Features\LivePrices\Models\GoldMarketSource::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'driver' => 'mock',
            'market_key' => 'egypt_local',
            'base_currency' => 'EGP',
            'endpoint_url' => $this->faker->url,
            'priority' => 1,
            'is_active' => true,
            'is_healthy' => true,
            'validation_threshold_pct' => 15.0,
        ];
    }
}
