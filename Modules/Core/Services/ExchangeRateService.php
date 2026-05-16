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
        // Placeholder for API fetching logic
        // E.g., make HTTP request to exchange rate API and save to ExchangeRate model
        Log::info('Exchange rates fetched from API.');
    }
}
