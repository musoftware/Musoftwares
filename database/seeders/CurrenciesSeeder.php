<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrenciesSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['currency' => 'USD', 'symbol' => '$', 'string_format' => '$%01.2f'],
            ['currency' => 'EGP', 'symbol' => 'e£', 'string_format' => 'e£%01.2f'],
            ['currency' => 'EUR', 'symbol' => '€', 'string_format' => 'e£%01.2f'],
            ['currency' => 'GBP', 'symbol' => '£', 'string_format' => '£%01.2f'],
            ['currency' => 'AED', 'symbol' => 'د.إ', 'string_format' => '%01.2f د.إ'],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(['currency' => $currency['currency']], $currency);
        }
    }
}
