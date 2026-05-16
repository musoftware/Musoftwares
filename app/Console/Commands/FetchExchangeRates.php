<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class FetchExchangeRates extends Command
{
    protected $signature = 'currency:fetch-rates';
    protected $description = 'Fetch daily exchange rates and update currencies table';

    public function handle()
    {
        $this->info('Fetching exchange rates...');

        // Mock fetch for demonstration
        $rates = [
            'EUR' => 0.92,
            'GBP' => 0.79,
            'JPY' => 150.23,
        ];

        $date = now()->toDateString();

        foreach ($rates as $code => $rate) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $code . ' Currency',
                    'exchange_rate' => $rate,
                    'exchange_rate_date' => $date,
                    'is_active' => true,
                    'updated_at' => now(),
                ]
            );
        }

        // Base currency USD
        DB::table('currencies')->updateOrInsert(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 1.0,
                'exchange_rate_date' => $date,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );

        $this->info('Exchange rates updated successfully.');
    }
}
