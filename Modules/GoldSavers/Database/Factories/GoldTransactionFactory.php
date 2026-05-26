<?php

namespace Modules\GoldSavers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\GoldSavers\Models\GoldTransaction;
use Modules\GoldSavers\Models\GoldWallet;

class GoldTransactionFactory extends Factory
{
    protected $model = GoldTransaction::class;

    public function definition()
    {
        $grams = $this->faker->randomFloat(2, 1, 50);
        $price = $this->faker->randomFloat(2, 3000, 4000);

        return [
            'wallet_id' => GoldWallet::factory(),
            'type' => $this->faker->randomElement(['buy', 'sell']),
            'grams' => $grams,
            'karat' => 24,
            'price_per_gram' => $price,
            'fees' => 0,
            'total_amount' => $grams * $price,
            'currency_id' => \App\Models\Currency::inRandomOrder()->first()->id ?? 1,
            'transaction_date' => now()->toDateString(),
            'vendor_name' => $this->faker->company,
            'notes' => $this->faker->sentence,
        ];
    }
}
