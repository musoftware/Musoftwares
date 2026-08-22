<?php

namespace App\Helper;

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
            $rates = [
                'USD' => 1.0,
            ];

            try {
                $currencies = \App\Models\Currency::all();
                $usdCurrency = $currencies->firstWhere('currency', 'USD');
                $usdId = $usdCurrency ? $usdCurrency->id : 1;

                foreach ($currencies as $currency) {
                    $code = strtoupper($currency->currency);
                    if ($code === 'USD') {
                        $rates['USD'] = 1.0;
                        continue;
                    }

                    $ex = \App\Models\CurrenciesExchange::where('currency1', $usdId)
                        ->where('currency2', $currency->id)
                        ->orderBy('id', 'desc')
                        ->first();

                    if ($ex && $ex->rate > 0) {
                        $rates[$code] = (float) $ex->rate;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore fallback failures
            }
        }

        static::$rates[$date] = $rates;

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
}
