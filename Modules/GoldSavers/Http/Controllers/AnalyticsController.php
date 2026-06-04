<?php

namespace Modules\GoldSavers\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\Controller;
use Modules\GoldSavers\Models\GoldWallet;
use Modules\GoldSavers\Models\GoldTransaction;
use Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;

class AnalyticsController extends Controller implements HasMiddleware
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
        
        $hasAnalytics = $user->hasModuleSubscription('gold-analytics');
        $hasBuySellAnalytics = $user->hasModuleSubscription('gold-buy-sell-analytics');
        
        $portfolioData = [];
        $karatData = [];
        $profitData = [];
        $transactionBreakdown = [];

        if ($hasAnalytics) {
            $wallets = GoldWallet::where('user_id', $user->id)->get();
            $walletIds = $wallets->pluck('id');
            
            foreach ($wallets as $wallet) {
                $portfolioData[] = [
                    'name' => $wallet->name,
                    'value' => (float) $wallet->balance_grams,
                ];
                
                $walletTransactions = GoldTransaction::where('wallet_id', $wallet->id)->get();
                $groupedByKarat = $walletTransactions->groupBy('karat');
                foreach ($groupedByKarat as $karat => $txs) {
                    $netGrams = $txs->where('type', 'buy')->sum('grams') - $txs->where('type', 'sell')->sum('grams');
                    if ($netGrams > 0) {
                        $karatData[] = [
                            'name' => $karat . 'K',
                            'value' => (float) $netGrams,
                        ];
                    }
                }
            }

            // Real transaction breakdown
            if ($hasBuySellAnalytics) {
                $buyTotal = GoldTransaction::whereIn('wallet_id', $walletIds)->where('type', 'buy')->sum('total_amount');
                $sellTotal = GoldTransaction::whereIn('wallet_id', $walletIds)->where('type', 'sell')->sum('total_amount');

                $transactionBreakdown = [
                    ['name' => __('general.buy'), 'value' => (float) $buyTotal],
                    ['name' => __('general.sell'), 'value' => (float) $sellTotal],
                ];
            }

            // Profit calculations based on Today's Price and Sales
            $months = collect(range(5, 0))->map(function($i) {
                $date = now()->subMonths($i);
                return [
                    'label' => $date->format('M Y'),
                    'year' => $date->year,
                    'month' => $date->month,
                    'end_of_month' => $date->copy()->endOfMonth()->format('Y-m-d H:i:s')
                ];
            });
            
            $latestPrice = GoldLivePrice::orderBy('fetched_at', 'desc')->first();
            $currentPrices = [
                24 => $latestPrice ? $latestPrice->price_gram_24k : 0,
                21 => $latestPrice ? $latestPrice->price_gram_21k : 0,
                18 => $latestPrice ? $latestPrice->price_gram_18k : 0,
                14 => $latestPrice ? $latestPrice->price_gram_14k : 0,
            ];

            foreach ($months as $monthInfo) {
                $endOfMonth = $monthInfo['end_of_month'];
                
                // 1. Holding Profit (Unrealized) up to this month
                $buysUpToMonth = GoldTransaction::whereIn('wallet_id', $walletIds)
                    ->where('type', 'buy')
                    ->where('transaction_date', '<=', $endOfMonth)
                    ->get();
                    
                $sellsUpToMonth = GoldTransaction::whereIn('wallet_id', $walletIds)
                    ->where('type', 'sell')
                    ->where('transaction_date', '<=', $endOfMonth)
                    ->get();

                $holdingProfit = 0;
                $holdingValue = 0;
                $costOfHoldings = 0;
                
                $buysByKarat = $buysUpToMonth->groupBy('karat');
                $sellsByKarat = $sellsUpToMonth->groupBy('karat');
                
                foreach($buysByKarat as $karat => $buys) {
                    $boughtGrams = $buys->sum('grams');
                    $boughtCost = $buys->sum('total_amount');
                    $soldGrams = isset($sellsByKarat[$karat]) ? $sellsByKarat[$karat]->sum('grams') : 0;
                    
                    $netKaratGrams = $boughtGrams - $soldGrams;
                    if ($netKaratGrams > 0) {
                        $karatPrice = $currentPrices[$karat] ?? ($currentPrices[21] ?? 0);
                        $holdingValue += $netKaratGrams * $karatPrice;
                        
                        $avgKaratCost = $boughtGrams > 0 ? $boughtCost / $boughtGrams : 0;
                        $costOfHoldings += $netKaratGrams * $avgKaratCost;
                    }
                }
                $holdingProfit = $holdingValue - $costOfHoldings;

                // 2. Sales Profit (Realized) IN this month
                $sellsInMonth = GoldTransaction::whereIn('wallet_id', $walletIds)
                    ->where('type', 'sell')
                    ->whereYear('transaction_date', $monthInfo['year'])
                    ->whereMonth('transaction_date', $monthInfo['month'])
                    ->get();
                    
                $salesProfit = 0;
                foreach ($sellsInMonth as $sell) {
                    $karat = $sell->karat;
                    $buysBeforeSell = GoldTransaction::whereIn('wallet_id', $walletIds)
                        ->where('type', 'buy')
                        ->where('karat', $karat)
                        ->where('transaction_date', '<=', $sell->transaction_date)
                        ->get();
                    
                    $boughtGrams = $buysBeforeSell->sum('grams');
                    $boughtCost = $buysBeforeSell->sum('total_amount');
                    $avgKaratCost = $boughtGrams > 0 ? $boughtCost / $boughtGrams : 0;
                    
                    $costOfSold = $sell->grams * $avgKaratCost;
                    $salesProfit += ($sell->total_amount - $costOfSold);
                }

                $profitData[] = [
                    'month' => $monthInfo['label'],
                    'holding_profit' => round($holdingProfit, 2),
                    'sales_profit' => round($salesProfit, 2),
                ];
            }
        }

        return Inertia::render('GoldSavers/Analytics/Index', [
            'hasAnalytics' => $hasAnalytics,
            'hasBuySellAnalytics' => $hasBuySellAnalytics,
            'portfolioData' => $portfolioData,
            'karatData' => $karatData,
            'profitData' => $profitData,
            'transactionBreakdown' => $transactionBreakdown,
        ]);
    }
}
