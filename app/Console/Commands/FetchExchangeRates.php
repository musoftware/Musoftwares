<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchExchangeRates extends Command
{
    protected $signature = 'currency:fetch-rates';
    protected $description = 'Fetch daily exchange rates and update exchange_rates table';

    public function handle()
    {
        $this->info('Fetching exchange rates...');

        // Mock fetch for demonstration
        $rates = [
            'EUR' => 0.92,
            'GBP' => 0.79,
            'JPY' => 150.23,
            'EGP' => 30.90,
            'SAR' => 3.75,
        ];

        $date = now()->toDateString();

        foreach ($rates as $code => $rate) {
            DB::table('exchange_rates')->updateOrInsert(
                [
                    'from_currency' => 'USD',
                    'to_currency' => $code,
                    'effective_date' => $date,
                ],
                [
                    'rate' => $rate,
                    'source' => 'api_auto',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->info('Exchange rates updated successfully.');
    }
}
