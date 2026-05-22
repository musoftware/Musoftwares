<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Core\Models\PointPackage;
use Modules\Core\Models\PointTransaction;
use App\Services\FinanceService;

class PointsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $packages = PointPackage::all();
        
        $user = $request->user();
        $preferredCurrency = $user->preferred_currency ?: 'USD';
        
        $financeService = app(FinanceService::class);
        $egpToPreferredRate = $financeService->getExchangeRate('EGP', $preferredCurrency);

        // Pre-convert package prices to user's preferred currency
        $packages = $packages->map(function ($pkg) use ($financeService, $preferredCurrency) {
            $pkg->price = $financeService->convertAmount((float) $pkg->price, $pkg->currency_code ?: 'EGP', $preferredCurrency);
            $pkg->currency_code = $preferredCurrency;
            return $pkg;
        });

        $transactions = PointTransaction::where('user_id', $user->id)->latest()->paginate(10);
        
        return Inertia::render('Core/Points/Index', [
            'packages' => $packages, 
            'transactions' => $transactions,
            'egpToPreferredRate' => $egpToPreferredRate,
        ]);
    }
}
