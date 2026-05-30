<?php

namespace Modules\GoldSavers\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldPriceHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;

class MarketController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                if (!Auth::user()->hasModuleSubscription('gold-saver')) {
                    return redirect()->route('subscriptions.plans')->with('error', __('gold_saver.gold_saver_subscription_required'));
                }
                return $next($request);
            }
        ];
    }

    public function index()
    {
        $user = Auth::user();
        
        $hasLivePrices = $user->hasModuleSubscription('gold-live-prices');
        $hasHistoricalCharts = $user->hasModuleSubscription('gold-historical-charts');
        
        $latestPrice = null;
        $historicalData = [];

        if ($hasLivePrices) {
            $latestPrice = GoldLivePrice::orderBy('fetched_at', 'desc')->first();
        }

        if ($hasHistoricalCharts) {
            // Fetch daily candles from GoldPriceHistory for the last 30 days
            $historicalData = GoldPriceHistory::where('market_key', 'local_egp')
                ->where('interval', 'day')
                ->where('karat', 21) // Focus on 21k as it's the standard for trends in Egypt
                ->where('period_start', '>=', now()->subDays(30)->startOfDay())
                ->orderBy('period_start', 'asc')
                ->get()
                ->map(function ($history) {
                    return [
                        'date' => \Carbon\Carbon::parse($history->period_start)->format('Y-m-d'),
                        'avg_24k' => (float) $history->avg_price, // Assuming avg_price holds the main value for the chart line
                        'min_24k' => (float) $history->low_price,
                        'max_24k' => (float) $history->high_price,
                    ];
                });
        }

        return Inertia::render('GoldSavers/Market/Index', [
            'hasLivePrices' => $hasLivePrices,
            'hasHistoricalCharts' => $hasHistoricalCharts,
            'latestPrice' => $latestPrice,
            'historicalData' => $historicalData,
        ]);
    }
}
