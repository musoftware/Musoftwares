<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Support\SsoSignature;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExchangeRateSyncController extends Controller
{
    /**
     * Sync currencies and exchange rates to external modules (e.g. goldsaversys).
     */
    public function sync(Request $request): JsonResponse
    {
        $signatureError = SsoSignature::verify($request, SsoSignature::systemFromHeaders($request), 'exchange-rates-sync');
        if ($signatureError !== null) {
            return response()->json(['error' => $signatureError], 401);
        }

        $request->validate([
            'module' => 'required|string',
            'date' => 'nullable|date',
        ]);

        $currencies = Currency::all();
        $currencyNames = [
            'USD' => 'US Dollar',
            'EGP' => 'Egyptian Pound',
            'EUR' => 'Euro',
            'GBP' => 'Pound Sterling',
            'AED' => 'UAE Dirham',
        ];

        // Fetch latest rates to determine the current_usd_rate for each currency
        $usdRates = [];
        foreach ($currencies as $currency) {
            if ($currency->id == 1 || strtoupper($currency->currency) === 'USD') {
                $usdRates[$currency->id] = 1.0;

                continue;
            }

            $rateRow = CurrenciesExchange::where('currency1', 1) // USD
                ->where('currency2', $currency->id)
                ->orderByDesc('date_string')
                ->first();

            $usdRates[$currency->id] = $rateRow ? (float) $rateRow->rate : 1.0;
        }

        // Build currencies list
        $currenciesData = [];
        foreach ($currencies as $currency) {
            $code = strtoupper($currency->currency);
            $currenciesData[] = [
                'code' => $code,
                'name' => $currencyNames[$code] ?? ($code.' Currency'),
                'symbol' => $currency->symbol,
                'current_usd_rate' => number_format($usdRates[$currency->id] ?? 1.0, 8, '.', ''),
                'is_active' => true,
            ];
        }

        // Fetch all exchange rates from the last 30 days
        $ratesQuery = CurrenciesExchange::with(['currencyFrom', 'currencyTo'])
            ->where('date_string', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('date_string')
            ->get();

        $ratesData = [];
        foreach ($ratesQuery as $ex) {
            if ($ex->currencyFrom && $ex->currencyTo) {
                $ratesData[] = [
                    'from_currency' => strtoupper($ex->currencyFrom->currency),
                    'to_currency' => strtoupper($ex->currencyTo->currency),
                    'rate' => number_format((float) $ex->rate, 8, '.', ''),
                    'date' => $ex->date_string instanceof Carbon ? $ex->date_string->toDateString() : (string) $ex->date_string,
                    'source' => 'monolith',
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'currencies' => $currenciesData,
                'rates' => $ratesData,
            ],
        ]);
    }
}
