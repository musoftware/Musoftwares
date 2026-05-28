<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(\Illuminate\Http\Request $request, \App\Services\IpGeolocationService $geoService)
    {
        $user = Auth::user();
        
        $usdCurrency = Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = null;
        if ($user && $user->currency_id) {
            $userCurrencyId = $user->currency_id;
        } else {
            // Use IP Geolocation for guest users
            $ipCurrencyCode = $geoService->getCurrencyCodeForIp($request->ip());
            if ($ipCurrencyCode) {
                $ipCurrency = Currency::where('currency', $ipCurrencyCode)->first();
                if ($ipCurrency) {
                    $userCurrencyId = $ipCurrency->id;
                }
            }
            // Fallback to USD if no IP currency match
            if (!$userCurrencyId) {
                $userCurrencyId = $usdCurrencyId;
            }
        }

        $userCurrency = Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $egpRate = 50; // Fallback
        if ($usdCurrency && $egpCurrencyId) {
            $egpRate = CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrencyId) ?: 50;
        }

        $basePricesEGP = config('saas.modules', []);
        $convertPrice = function($egpPrice) use ($egpRate, $rate, $currencyCode) {
            if ($currencyCode === 'EGP') {
                return round($egpPrice);
            }
            $usdPrice = $egpPrice / $egpRate;
            $converted = $usdPrice * $rate;
            return psychological_price($converted);
        };

        $pricingService = new \App\Services\PricingService();
        $serviceItems = $pricingService->getServiceItems($convertPrice);

        return Inertia::render('Public/Home', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'serviceItems' => $serviceItems,
            'currency' => $currencyCode,
        ]);
    }
}
