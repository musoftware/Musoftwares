<?php

namespace Modules\GoldSavers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\GoldSavers\Models\GoldPrice;
use Modules\GoldSavers\Models\GoldWallet;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get latest price or fallback
        $latestPrice = GoldPrice::orderBy('date', 'desc')->first();
        
        // Fetch user's wallets
        $wallets = GoldWallet::where('user_id', $user->id)
            ->with(['transactions' => function($q) {
                $q->orderBy('transaction_date', 'desc')->take(5);
            }])
            ->get();

        // Calculate portfolio summary
        $totalGrams = $wallets->sum('balance_grams');
        $totalInvested = $wallets->sum('balance_amount');
        
        $currentValue = 0;
        if ($latestPrice && $totalGrams > 0) {
            // Assuming 21k is the standard for display if mixed, or calculate exactly if needed.
            // For MVP dashboard summary, we'll use 21k price * total grams.
            $currentValue = $totalGrams * ($latestPrice->karat_21 ?? 0);
        }

        $totalProfit = $currentValue - $totalInvested;
        $profitPercentage = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

        return Inertia::render('GoldSavers/Dashboard', [
            'wallets' => $wallets,
            'latestPrice' => $latestPrice,
            'portfolio' => [
                'total_grams' => $totalGrams,
                'total_invested' => $totalInvested,
                'current_value' => $currentValue,
                'total_profit' => $totalProfit,
                'profit_percentage' => round($profitPercentage, 2),
            ]
        ]);
    }
}
