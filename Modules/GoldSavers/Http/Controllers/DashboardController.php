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
        $priceChanges = null;
        
        if ($hasLivePrices) {
            $latestPrice = GoldLivePrice::orderBy('fetched_at', 'desc')->first();
            
            if ($latestPrice) {
                // Fetch the closest price before today's latest to calculate daily change
                $previousPrice = GoldLivePrice::where('id', '!=', $latestPrice->id)
                    ->whereDate('fetched_at', '<', $latestPrice->fetched_at)
                    ->orderBy('fetched_at', 'desc')
                    ->first();

                if ($previousPrice) {
                    $priceChanges = [
                        'price_gram_24k' => $this->calculatePercentageChange($previousPrice->price_gram_24k, $latestPrice->price_gram_24k),
                        'price_gram_21k' => $this->calculatePercentageChange($previousPrice->price_gram_21k, $latestPrice->price_gram_21k),
                        'price_gram_18k' => $this->calculatePercentageChange($previousPrice->price_gram_18k, $latestPrice->price_gram_18k),
                        'price_ounce_usd' => $this->calculatePercentageChange($previousPrice->price_ounce_usd, $latestPrice->price_ounce_usd),
                    ];
                }
            }
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
            $smartInsights = \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->endOfDay(), function() use ($wallets, $totalGrams, $totalInvested, $currentValue, $totalProfit, $latestPrice, $priceChanges) {
                $service = new \Modules\GoldSavers\Services\GeminiInsightService();
                $portfolioData = [
                    'total_grams' => $totalGrams,
                    'total_invested' => $totalInvested,
                    'current_value' => $currentValue,
                    'total_profit' => $totalProfit,
                ];
                return $service->generateInsights($wallets, $portfolioData, $latestPrice, $priceChanges);
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
            'priceChanges' => $priceChanges,
            'hasSmartInsights' => $hasSmartInsights,
            'smartInsights' => $smartInsights,
        ]);
    }

    private function calculatePercentageChange($old, $new)
    {
        if (!$old || $old == 0) return 0;
        return round((($new - $old) / $old) * 100, 2);
    }

    public function refreshPrices()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('gold:fetch-local');
            \Illuminate\Support\Facades\Artisan::call('gold:fetch-global');
            \Illuminate\Support\Facades\Artisan::call('gold_price:fetcher');
            \Illuminate\Support\Facades\Artisan::call('gold_world_price:fetcher');
            return redirect()->back()->with('success', __('general.prices_refreshed_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('general.failed_to_refresh_prices'));
        }
    }
}
