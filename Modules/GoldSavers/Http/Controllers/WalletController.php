<?php

namespace Modules\GoldSavers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\GoldSavers\Models\GoldWallet;
use Modules\GoldSavers\Models\GoldTransaction;
use Modules\GoldSavers\Services\GoldWalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WalletController extends Controller implements HasMiddleware
{
    protected $walletService;

    public function __construct(GoldWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!Auth::user()->hasModuleSubscription('gold-saver')) {
                    return redirect()->route('subscriptions.plans')->with('error', __('gold_saver.gold_saver_subscription_required'));
                }
                return $next($request);
            }),
        ];
    }

    public function index()
    {
        $user = Auth::user();
        $wallets = GoldWallet::where('user_id', $user->id)
            ->with('transactions')
            ->get();

        return Inertia::render('GoldSavers/Wallets/Index', [
            'wallets' => $wallets,
            'hasMultiWallets' => $user->hasModuleSubscription('gold-multi-wallets'),
            'hasGoalTracking' => $user->hasModuleSubscription('gold-goal-tracking'),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->hasModuleSubscription('gold-multi-wallets')) {
            $walletCount = GoldWallet::where('user_id', $user->id)->count();
            if ($walletCount >= 1) {
                abort(403, __('gold_saver.upgrade_for_multi_wallets'));
            }
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'goal_type' => 'nullable|string',
            'target_grams' => 'nullable|numeric|min:0',
            'target_amount' => 'nullable|numeric|min:0',
        ]);

        $hasGoalTracking = $user->hasModuleSubscription('gold-goal-tracking');

        $this->walletService->createWallet(
            $user->id,
            $validated['name'],
            $validated['goal_type'] ?? 'Investment',
            $hasGoalTracking ? ($validated['target_grams'] ?? 0) : 0,
            $hasGoalTracking ? ($validated['target_amount'] ?? 0) : 0,
            $user->tenant_id
        );

        return redirect()->back()->with('success', __('gold_saver.wallet_created_successfully'));
    }

    public function update(Request $request, GoldWallet $wallet)
    {
        $user = Auth::user();
        if ($wallet->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'goal_type' => 'nullable|string',
            'target_grams' => 'nullable|numeric|min:0',
            'target_amount' => 'nullable|numeric|min:0',
        ]);

        $hasGoalTracking = $user->hasModuleSubscription('gold-goal-tracking');

        $wallet->update([
            'name' => $validated['name'],
            'goal_type' => $validated['goal_type'] ?? 'Investment',
            'target_grams' => $hasGoalTracking ? ($validated['target_grams'] ?? 0) : 0,
            'target_amount' => $hasGoalTracking ? ($validated['target_amount'] ?? 0) : 0,
        ]);

        return redirect()->back()->with('success', __('gold_saver.wallet_updated_successfully'));
    }

    public function show(GoldWallet $wallet)
    {
        $user = Auth::user();
        if ($wallet->user_id !== $user->id) {
            abort(403);
        }

        $wallet->load(['transactions' => function($q) {
            $q->orderBy('transaction_date', 'desc');
        }]);

        // Fetch Live Prices for current value calculation
        $latestPrice = null;
        if ($user->hasModuleSubscription('gold-live-prices')) {
            $latestPrice = \Modules\GoldSavers\app\Features\LivePrices\Models\GoldLivePrice::orderBy('fetched_at', 'desc')->first();
        }

        // Calculate Average Cost and Break-Even
        $averageCost = $wallet->balance_grams > 0 ? ($wallet->balance_amount / $wallet->balance_grams) : 0;
        
        $currentValue = 0;
        if ($latestPrice && $wallet->balance_grams > 0) {
            $currentValue = $wallet->balance_grams * ($latestPrice->price_gram_21k ?? 0); // Default to 21k
        }

        $isProfit = $currentValue > $wallet->balance_amount;

        // Calculate Time-to-Goal (Gamification)
        $monthsToGoal = null;
        if ($wallet->target_grams > 0 && $wallet->balance_grams < $wallet->target_grams && $wallet->transactions->count() > 0) {
            $firstTx = $wallet->transactions->sortBy('transaction_date')->first();
            if ($firstTx) {
                $daysSinceStart = \Carbon\Carbon::parse($firstTx->transaction_date)->diffInDays(now());
                $monthsSinceStart = max(1, $daysSinceStart / 30); // Avoid division by zero, min 1 month
                $avgMonthlyGrams = $wallet->balance_grams / $monthsSinceStart;
                
                if ($avgMonthlyGrams > 0) {
                    $remainingGrams = $wallet->target_grams - $wallet->balance_grams;
                    $monthsToGoal = ceil($remainingGrams / $avgMonthlyGrams);
                }
            }
        }

        return Inertia::render('GoldSavers/Wallets/Show', [
            'wallet' => $wallet,
            'hasGoalTracking' => $user->hasModuleSubscription('gold-goal-tracking'),
            'latestPrice' => $latestPrice,
            'gamification' => [
                'averageCost' => round($averageCost, 2),
                'currentValue' => round($currentValue, 2),
                'isProfit' => $isProfit,
                'profitAmount' => round($currentValue - $wallet->balance_amount, 2),
                'monthsToGoal' => $monthsToGoal,
            ]
        ]);
    }

    public function addTransaction(Request $request, GoldWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:buy,sell',
            'grams' => 'required|numeric|min:0.01',
            'karat' => 'required|integer',
            'price_per_gram' => 'required|numeric|min:0.01',
            'fees' => 'nullable|numeric|min:0',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['total_amount'] = ($validated['grams'] * $validated['price_per_gram']) + ($validated['fees'] ?? 0);
        
        $this->walletService->addTransaction($wallet, $validated);

        return redirect()->back()->with('success', __('gold_saver.transaction_added_successfully'));
    }

    public function destroy(GoldWallet $wallet)
    {
        if ($wallet->user_id !== Auth::id()) {
            abort(403);
        }

        $wallet->transactions()->delete();
        $wallet->delete();

        return redirect()->route('isaas.gold-savers.wallets.index')->with('success', __('gold_saver.wallet_deleted_successfully'));
    }

    public function updateTransaction(Request $request, GoldWallet $wallet, GoldTransaction $transaction)
    {
        if ($wallet->user_id !== Auth::id() || $transaction->wallet_id !== $wallet->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:buy,sell',
            'grams' => 'required|numeric|min:0.01',
            'karat' => 'required|integer',
            'price_per_gram' => 'required|numeric|min:0.01',
            'fees' => 'nullable|numeric|min:0',
            'transaction_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['total_amount'] = ($validated['grams'] * $validated['price_per_gram']) + ($validated['fees'] ?? 0);

        $transaction->update($validated);
        $wallet->recalculateBalance();

        return redirect()->back()->with('success', __('gold_saver.transaction_updated_successfully'));
    }

    public function destroyTransaction(GoldWallet $wallet, GoldTransaction $transaction)
    {
        if ($wallet->user_id !== Auth::id() || $transaction->wallet_id !== $wallet->id) {
            abort(403);
        }

        $transaction->delete();
        $wallet->recalculateBalance();

        return redirect()->back()->with('success', __('gold_saver.transaction_deleted_successfully'));
    }
}
