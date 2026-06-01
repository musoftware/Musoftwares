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
        $priceChanges = null;
        $historicalData = [];

        if ($hasLivePrices) {
            $latestPrice = GoldLivePrice::orderBy('fetched_at', 'desc')->first();
            
            if ($latestPrice) {
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

        if ($hasHistoricalCharts) {
            $karat = request('karat', 21);
            $period = request('period', '1m');
            
            $startDate = now()->subDays(30)->startOfDay(); // Default 1M
            if ($period === '1w') $startDate = now()->subDays(7)->startOfDay();
            if ($period === '6m') $startDate = now()->subMonths(6)->startOfDay();
            if ($period === '1y') $startDate = now()->subYear()->startOfDay();

            // Fetch daily candles from GoldPriceHistory
            $historicalData = GoldPriceHistory::where('market_key', 'local_egp')
                ->where('interval', 'day')
                ->where('karat', $karat)
                ->where('period_start', '>=', $startDate)
                ->orderBy('period_start', 'asc')
                ->get()
                ->map(function ($history) use ($karat) {
                    return [
                        'date' => \Carbon\Carbon::parse($history->period_start)->format('Y-m-d'),
                        "avg_{$karat}k" => (float) $history->avg_price,
                        "min_{$karat}k" => (float) $history->low_price,
                        "max_{$karat}k" => (float) $history->high_price,
                    ];
                });
        }

        return Inertia::render('GoldSavers/Market/Index', [
            'hasLivePrices' => $hasLivePrices,
            'hasHistoricalCharts' => $hasHistoricalCharts,
            'latestPrice' => $latestPrice,
            'priceChanges' => $priceChanges,
            'historicalData' => $historicalData,
            'filters' => [
                'karat' => request('karat', 21),
                'period' => request('period', '1m')
            ]
        ]);
    }

    private function calculatePercentageChange($old, $new)
    {
        if (!$old || $old == 0) return 0;
        return round((($new - $old) / $old) * 100, 2);
    }
}
