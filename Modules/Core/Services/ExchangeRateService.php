<?php

namespace Modules\Core\Services;

use Modules\Core\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    /**
     * Get the exchange rate for a specific date.
     * Tries exact date match first, then falls back to the closest previous date.
     */
    public function getRate(string $from, string $to, $date = null): float|string
    {
        if ($from === $to) {
            return 1.0;
        }

        $date = $date ? Carbon::parse($date) : now();

        $rate = ExchangeRate::where('from_currency', $from)
            ->where('to_currency', $to)
            ->where('effective_date', $date->toDateString())
            ->first();

        if (!$rate) {
            $rate = ExchangeRate::where('from_currency', $from)
                ->where('to_currency', $to)
                ->where('effective_date', '<', $date->toDateString())
                ->orderBy('effective_date', 'desc')
                ->first();
        }

        if (!$rate) {
            // Default rate if none found (or throw exception)
            return 1.0;
        }

        return $rate->rate;
    }

    /**
     * Convert an amount between currencies.
     * Returns: [amount, currency, business_amount, business_currency, exchange_rate, exchange_rate_date]
     */
    public function convertAmount(float $amount, string $from, string $to, $date = null): array
    {
        $date = $date ? Carbon::parse($date) : now();
        $rateValue = $this->getRate($from, $to, $date);

        $businessAmount = $amount * (float)$rateValue;

        return [
            $amount,
            $from,
            $businessAmount,
            $to,
            $rateValue,
            $date->toDateString()
        ];
    }

    /**
     * Fetch rates from API (for scheduler).
     */
    public function fetchRatesFromAPI(): void
    {
        $supportedCurrencies = ['USD', 'EUR', 'GBP', 'TRY', 'CAD', 'AUD'];
        
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)->get('https://open.er-api.com/v6/latest/USD');
            
            if ($response->successful()) {
                $data = $response->json();
                $rates = $data['rates'] ?? [];
                
                foreach ($supportedCurrencies as $from) {
                    $fromRateToUSD = $rates[$from] ?? null;
                    if (!$fromRateToUSD) continue;
                    
                    foreach ($supportedCurrencies as $to) {
                        if ($from === $to) continue;
                        
                        $toRateToUSD = $rates[$to] ?? null;
                        if (!$toRateToUSD) continue;
                        
                        $rate = (float)$toRateToUSD / (float)$fromRateToUSD;
                        
                        ExchangeRate::updateOrCreate([
                            'from_currency' => $from,
                            'to_currency' => $to,
                            'effective_date' => now()->toDateString(),
                        ], [
                            'rate' => $rate,
                            'source' => 'api_auto',
                        ]);
                    }
                }
                Log::info('Exchange rates updated successfully from API.');
            } else {
                Log::warning('Exchange rate API returned non-success code: ' . $response->status() . '. Initializing fallbacks.');
                $this->populateDefaultFallbackRates();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch exchange rates from API: ' . $e->getMessage() . '. Initializing fallbacks.');
            $this->populateDefaultFallbackRates();
        }
    }

    /**
     * Populates standard fallback rates if the API is offline.
     */
    public function populateDefaultFallbackRates(): void
    {
        $fallbackRates = [
            'USD' => ['EUR' => 0.92, 'GBP' => 0.79, 'TRY' => 32.25, 'CAD' => 1.36, 'AUD' => 1.50],
            'EUR' => ['USD' => 1.09, 'GBP' => 0.86, 'TRY' => 35.05, 'CAD' => 1.48, 'AUD' => 1.63],
            'GBP' => ['USD' => 1.27, 'EUR' => 1.16, 'TRY' => 40.82, 'CAD' => 1.72, 'AUD' => 1.90],
            'TRY' => ['USD' => 0.031, 'EUR' => 0.029, 'GBP' => 0.025, 'CAD' => 0.042, 'AUD' => 0.047],
            'CAD' => ['USD' => 0.74, 'EUR' => 0.68, 'GBP' => 0.58, 'TRY' => 23.71, 'AUD' => 1.10],
            'AUD' => ['USD' => 0.67, 'EUR' => 0.61, 'GBP' => 0.53, 'TRY' => 21.50, 'CAD' => 0.91],
        ];

        foreach ($fallbackRates as $from => $targets) {
            foreach ($targets as $to => $rate) {
                ExchangeRate::updateOrCreate([
                    'from_currency' => $from,
                    'to_currency' => $to,
                    'effective_date' => now()->toDateString(),
                ], [
                    'rate' => $rate,
                    'source' => 'api_auto',
                ]);
            }
        }
        Log::info('Fallback exchange rates populated.');
    }
}
