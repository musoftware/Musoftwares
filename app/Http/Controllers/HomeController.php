<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->getHost() === 'lance.musoftwares.com') {
            return Inertia::render('Freelance/Landing', [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
            ]);
        }

        return Inertia::render('Public/Home', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function portfolio()
    {
        return Inertia::render('Public/Portfolio', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function platforms()
    {
        return Inertia::render('Public/Platforms', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function platformCrm()
    {
        return Inertia::render('Public/Platforms/Crm', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function platformErp()
    {
        return Inertia::render('Public/Platforms/Erp', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function platformCloud()
    {
        return Inertia::render('Public/Platforms/Cloud', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function solutions()
    {
        return Inertia::render('Public/Solutions', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function solutionHealthcare()
    {
        return Inertia::render('Public/Solutions/Healthcare', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function solutionEducation()
    {
        return Inertia::render('Public/Solutions/Education', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function solutionEcommerce()
    {
        return Inertia::render('Public/Solutions/Ecommerce', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function solutionRealEstate()
    {
        return Inertia::render('Public/Solutions/RealEstate', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function solutionFinance()
    {
        return Inertia::render('Public/Solutions/Finance', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function company()
    {
        return Inertia::render('Public/Company', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function companyAbout()
    {
        return Inertia::render('Public/Company/About', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function companyCareers()
    {
        return Inertia::render('Public/Company/Careers', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function companyContact()
    {
        return Inertia::render('Public/Company/Contact', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function privacyPolicy()
    {
        return Inertia::render('Public/Legal/Privacy', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function termsOfService()
    {
        return Inertia::render('Public/Legal/Terms', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function cookiePolicy()
    {
        return Inertia::render('Public/Legal/Cookies', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
    }

    public function pricing(\Illuminate\Http\Request $request, \App\Services\IpGeolocationService $geoService)
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

        return Inertia::render('Public/Pricing', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'serviceItems' => $serviceItems,
            'currency' => $currencyCode,
        ]);
    }
}
