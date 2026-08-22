<?php

namespace App\Helpers;

use App\Models\AdminSettings;
use App\Models\Currency;
use Illuminate\Support\Facades\Http;

class CurrencyHelper
{
    public static $api = '25d8ea622f7c48f1a7c8d171b8f56843';

    public static $rates = [];

    public static function prepare($date)
    {
        // format date to YYYY-MM-DD
        $date = date('Y-m-d', strtotime($date));

        if (isset(static::$rates[$date])) {
            return static::$rates[$date];
        }

        $rates = null;

        try {
            $request_url = 'https://openexchangerates.org/api/historical/'.$date.'.json?app_id='.static::$api;

            $response = Http::withOptions(['verify' => false])
                ->timeout(5)
                ->get($request_url);

            if ($response->successful()) {
                $json = $response->json();
                if (! empty($json['rates']) && is_array($json['rates'])) {
                    $rates = $json['rates'];
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CurrencyHelper prepare external API failed: '.$e->getMessage());
        }

        // Fallback to database exchange rates if external API fails or rates key missing
        if (! $rates) {
            $rates = static::getFallbackRatesFromDb();
        }

        static::$rates[$date] = $rates;

        return $rates;
    }

    /**
     * Fallback to database exchange rates (relative to USD)
     */
    protected static function getFallbackRatesFromDb(): array
    {
        $rates = [
            'USD' => 1.0,
        ];

        try {
            $currencies = Currency::all();
            $usdCurrency = $currencies->firstWhere('currency', 'USD');
            $usdId = $usdCurrency ? $usdCurrency->id : 1;

            foreach ($currencies as $currency) {
                $code = strtoupper($currency->currency);
                if ($code === 'USD') {
                    $rates['USD'] = 1.0;
                    continue;
                }

                // Check rate from USD -> Currency
                $ex = \App\Models\CurrenciesExchange::where('currency1', $usdId)
                    ->where('currency2', $currency->id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($ex && $ex->rate > 0) {
                    $rates[$code] = (float) $ex->rate;
                } else {
                    // Check reverse Currency -> USD
                    $reverse = \App\Models\CurrenciesExchange::where('currency1', $currency->id)
                        ->where('currency2', $usdId)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($reverse && $reverse->rate > 0) {
                        $rates[$code] = (float) (1 / $reverse->rate);
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CurrencyHelper fallback from DB failed: '.$e->getMessage());
        }

        return $rates;
    }

    public static function getRate($date, $currency1, $currency2)
    {
        $rates = static::prepare($date);

        $currency1 = strtoupper($currency1);
        $currency2 = strtoupper($currency2);

        if ($currency1 == $currency2) {
            return 1;
        }

        $rate1 = $rates[$currency1] ?? null;
        $rate2 = $rates[$currency2] ?? null;

        if ($currency1 == 'USD') {
            return $rate2 ?? 1.0;
        }
        if ($currency2 == 'USD') {
            return ($rate1 && $rate1 > 0) ? (1 / $rate1) : 1.0;
        }

        if ($rate1 && $rate1 > 0 && $rate2) {
            return $rate2 / $rate1;
        }

        return 1.0;
    }

    /**
     * Get a standardized frontend currency object representation.
     * This avoids duplicating currency resolution logic in the UI.
     *
     * @param  int|string|null  $currencyId
     */
    public static function getFrontendCurrency($currencyId): ?array
    {
        if (! $currencyId) {
            return null;
        }

        $currency = is_numeric($currencyId)
            ? Currency::find($currencyId)
            : Currency::where('currency', $currencyId)->first();

        if (! $currency) {
            return [
                'id' => is_numeric($currencyId) ? (int) $currencyId : null,
                'currency' => is_string($currencyId) ? strtoupper($currencyId) : 'USD',
                'symbol' => is_string($currencyId) ? strtoupper($currencyId) : '$',
                'string_format' => null,
            ];
        }

        return [
            'id' => $currency->id,
            'currency' => $currency->currency,
            'symbol' => $currency->symbol,
            'string_format' => $currency->string_format,
        ];
    }

    /**
     * Get the standardized frontend business currency object.
     */
    public static function getBusinessCurrency(): ?array
    {
        return self::getFrontendCurrency(AdminSettings::business_currency());
    }
}
