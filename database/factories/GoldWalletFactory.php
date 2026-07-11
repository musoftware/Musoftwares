<?php

namespace Database\Factories;

use App\Models\Modules\GoldSavers\Models\GoldWallet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoldWallet>
 */
class GoldWalletFactory extends Factory
{
    protected $model = \Modules\GoldSavers\Models\GoldWallet::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'goal_type' => $this->faker->randomElement(['investment', 'retirement', 'custom']),
            'target_grams' => $this->faker->randomFloat(2, 50, 1000),
            'target_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'balance_grams' => 0,
            'balance_amount' => 0,
            'currency_id' => 1,
            'is_active' => true,
        ];
    }
}
