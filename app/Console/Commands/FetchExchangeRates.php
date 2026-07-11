<?php

namespace App\Console\Commands;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchExchangeRates extends Command
{
    protected $signature = 'currency:fetch-rates';

    protected $description = 'Fetch daily exchange rates and update currencies_exchanges table';

    public function handle()
    {
        $this->info('Fetching exchange rates from open.er-api.com...');

        try {
            $response = Http::timeout(10)->withoutVerifying()->get('https://open.er-api.com/v6/latest/USD');

            if (! $response->successful() || ! isset($response['rates'])) {
                $this->error('Failed to fetch exchange rates. Falling back to cached rates.');

                return;
            }

            $rates = $response['rates'];
            $date = now()->toDateString();
            $currencies = Currency::all();

            foreach ($currencies as $from) {
                foreach ($currencies as $to) {
                    if ($from->id === $to->id) {
                        continue;
                    }

                    // Calculate cross rate via USD
                    $fromRate = $rates[$from->currency] ?? null;
                    $toRate = $rates[$to->currency] ?? null;

                    if ($fromRate && $toRate && $fromRate > 0) {
                        $crossRate = $toRate / $fromRate;

                        CurrenciesExchange::updateOrCreate(
                            [
                                'currency1' => $from->id,
                                'currency2' => $to->id,
                                'date_string' => $date,
                            ],
                            [
                                'rate' => $crossRate,
                            ]
                        );
                    }
                }
            }

            $this->info('Exchange rates updated successfully.');
        } catch (\Exception $e) {
            $this->error('Exception fetching rates: '.$e->getMessage());
        }
    }
}
