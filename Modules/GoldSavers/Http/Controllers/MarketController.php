<?php

namespace Modules\GoldSavers\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;

class MarketController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                if (!Auth::user()->hasModuleSubscription('gold-saver')) {
                    abort(403, __('gold_saver.gold_saver_subscription_required'));
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
            // Get last 30 days of price data (simplified for now: group by date)
            $historicalData = GoldLivePrice::selectRaw('DATE(fetched_at) as date, MAX(price_gram_24k) as max_24k, MIN(price_gram_24k) as min_24k, AVG(price_gram_24k) as avg_24k')
                ->where('fetched_at', '>=', now()->subDays(30))
                ->groupByRaw('DATE(fetched_at)')
                ->orderBy('date', 'asc')
                ->get();
        }

        return Inertia::render('GoldSavers/Market/Index', [
            'hasLivePrices' => $hasLivePrices,
            'hasHistoricalCharts' => $hasHistoricalCharts,
            'latestPrice' => $latestPrice,
            'historicalData' => $historicalData,
        ]);
    }
}
