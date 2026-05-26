<?php

namespace Modules\GoldSavers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GoldSavers\Models\GoldWallet;
use App\Models\User;
use App\Models\Tenant;

class GoldWalletFactory extends Factory
{
    protected $model = GoldWallet::class;

    public function definition()
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'name' => $this->faker->words(2, true),
            'goal_type' => $this->faker->randomElement(['investment', 'saving', 'trading']),
            'target_grams' => $this->faker->randomFloat(2, 10, 500),
            'target_amount' => $this->faker->randomFloat(2, 50000, 500000),
            'balance_grams' => 0,
            'balance_amount' => 0,
            'currency_id' => \App\Models\Currency::inRandomOrder()->first()->id ?? 1,
            'is_active' => true,
        ];
    }
}
