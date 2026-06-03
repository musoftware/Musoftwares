<?php

namespace App\Helpers;

use App\Models\User;
use App\Models\Currency;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use http\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // get all currencies
        $request_url = 'https://openexchangerates.org/api/historical/' . $date . '.json?app_id=' . static::$api;

        $response = Http::withOptions(['verify' => false])
            ->get($request_url);

        $rates = $response->json()['rates'];

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
        if ($currency1 == 'USD') {
            return $rates[$currency2];
        }
        if ($currency2 == 'USD') {
            return 1 / $rates[$currency1];
        }
        return $rates[$currency2] / $rates[$currency1];
    }

    /**
     * Get a standardized frontend currency object representation.
     * This avoids duplicating currency resolution logic in the UI.
     * 
     * @param int|string|null $currencyId
     * @return array|null
     */
    public static function getFrontendCurrency($currencyId): ?array
    {
        if (!$currencyId) {
            return null;
        }

        $currency = is_numeric($currencyId) 
            ? \App\Models\Currency::find($currencyId) 
            : \App\Models\Currency::where('currency', $currencyId)->first();

        if (!$currency) {
            return [
                'id' => is_numeric($currencyId) ? (int)$currencyId : null,
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
     * 
     * @return array|null
     */
    public static function getBusinessCurrency(): ?array
    {
        return self::getFrontendCurrency(\App\Models\AdminSettings::business_currency());
    }
}
