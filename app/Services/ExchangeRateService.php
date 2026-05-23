<?php

namespace App\Services;

use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Cache;

class ExchangeRateService
{
    /**
     * Get exchange rate between two currencies.
     */
    public function getRate(string $fromCurrency, string $toCurrency): float
    {
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        // We can just query CurrenciesExchange model logic for a 1 USD amount, 
        // then mathematically derive the rate if needed, or if it supports arbitrary pairs.
        // The legacy system has CurrenciesExchange::RateToday($amount, $from, $to).
        return (float) CurrenciesExchange::RateToday(1.0, $fromCurrency, $toCurrency);
    }
}
