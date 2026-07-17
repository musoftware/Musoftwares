<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExchangeRateSyncController extends Controller
{
    /**
     * Sync currencies and exchange rates to external modules (e.g. goldsaversys).
     */
    public function sync(Request $request): JsonResponse
    {
        $secret = (string) config('services.goldsaversys.shared_secret', '');

        $signature = $request->header('X-GoldSaver-Signature');
        $timestamp = $request->header('X-GoldSaver-Timestamp');
        $system = $request->header('X-GoldSaver-System');

        if (! $signature || ! $timestamp || ! $system) {
            return response()->json(['error' => 'missing_signature_headers'], 401);
        }

        // Prevent replay attacks (allow 5 minute clock drift)
        if (abs(now()->timestamp - (int) $timestamp) > 300) {
            return response()->json(['error' => 'signature_expired'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp.'.exchange-rates-sync', $secret);
        if (! hash_equals($expected, $signature)) {
            Log::warning('SSO Exchange Rates Sync signature mismatch', [
                'ip' => $request->ip(),
                'received' => $signature,
                'expected' => $expected,
            ]);

            return response()->json(['error' => 'invalid_signature'], 401);
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
                'name' => $currencyNames[$code] ?? ($code . ' Currency'),
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
                    'date' => $ex->date_string instanceof \Carbon\Carbon ? $ex->date_string->toDateString() : (string) $ex->date_string,
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
