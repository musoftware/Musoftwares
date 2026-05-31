<?php

namespace Modules\GoldSavers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\Models\GoldWallet;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasModuleSubscription('gold-saver')) {
            return redirect()->route('subscriptions.plans')->with('error', __('gold_saver.gold_saver_subscription_required'));
        }

        // Get latest price or fallback
        $hasLivePrices = $user->hasModuleSubscription('gold-live-prices');
        $latestPrice = null;
        
        if ($hasLivePrices) {
            $latestPrice = GoldLivePrice::orderBy('fetched_at', 'desc')->first();
        }
        
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
            $currentValue = $totalGrams * ($latestPrice->price_gram_21k ?? 0);
        }

        $totalProfit = $currentValue - $totalInvested;
        $profitPercentage = $totalInvested > 0 ? ($totalProfit / $totalInvested) * 100 : 0;

        $hasSmartInsights = $user->hasModuleSubscription('gold-smart-insights');
        $smartInsights = [];
        if ($hasSmartInsights) {
            $cacheKey = "user_{$user->id}_gold_insights_" . date('Y-m-d');
            $smartInsights = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->endOfDay(), function() use ($wallets, $totalGrams, $totalInvested, $currentValue, $totalProfit, $latestPrice) {
                $service = new \Modules\GoldSavers\Services\GeminiInsightService();
                $portfolioData = [
                    'total_grams' => $totalGrams,
                    'total_invested' => $totalInvested,
                    'current_value' => $currentValue,
                    'total_profit' => $totalProfit,
                ];
                return $service->generateInsights($wallets, $portfolioData, $latestPrice);
            });
        }

        return Inertia::render('GoldSavers/Dashboard', [
            'wallets' => $wallets,
            'latestPrice' => $latestPrice,
            'portfolio' => [
                'total_grams' => $totalGrams,
                'total_invested' => $totalInvested,
                'current_value' => $currentValue,
                'total_profit' => $totalProfit,
                'profit_percentage' => round($profitPercentage, 2),
            ],
            'hasLivePrices' => $hasLivePrices,
            'hasSmartInsights' => $hasSmartInsights,
            'smartInsights' => $smartInsights,
        ]);
    }
}
