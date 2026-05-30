<?php

namespace Modules\GoldSavers\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Modules\GoldSavers\Models\GoldWallet;
use Modules\GoldSavers\Models\GoldTransaction;
use Illuminate\Routing\Controllers\HasMiddleware;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReportsController extends Controller implements HasMiddleware
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
        $hasReports = $user->hasModuleSubscription('gold-investment-reports');

        $wallets = [];
        if ($hasReports) {
            $wallets = GoldWallet::with('currency')->where('user_id', $user->id)->get(['id', 'name', 'balance_grams', 'currency_id']);
        }

        return Inertia::render('GoldSavers/Reports/Index', [
            'hasReports' => $hasReports,
            'wallets' => $wallets,
        ]);
    }

    public function downloadPdf(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasModuleSubscription('gold-investment-reports')) {
            return redirect()->back()->with('error', __('gold_saver.reports_locked_message'));
        }

        $request->validate([
            'wallet_id' => 'required|string',
            'period' => 'required|string|in:monthly,yearly,all_time',
        ]);

        $walletId = $request->input('wallet_id');
        $period = $request->input('period');

        $walletsQuery = GoldWallet::where('user_id', $user->id);
        $walletName = 'All Wallets';
        
        if ($walletId !== 'all') {
            $walletsQuery->where('id', $walletId);
            $wallet = $walletsQuery->with('currency')->firstOrFail();
            $walletName = $wallet->name;
            $walletIds = [$wallet->id];
            
            if (!$wallet->currency) {
                throw new \Exception("Wallet {$wallet->id} is missing an associated currency relation.");
            }
            $currencyCode = $wallet->currency->code;
        } else {
            $walletIds = $walletsQuery->pluck('id')->toArray();
            $businessCurrency = \App\Models\AdminSettings::business_currency();
            if (!$businessCurrency) {
                 throw new \Exception("System business currency is missing.");
            }
            // For MVP, we'll just use the business currency code if available, assuming it returns an object or ID.
            // Actually, let's just fetch the currency of the first wallet.
            $firstWallet = GoldWallet::with('currency')->where('user_id', $user->id)->first();
            if (!$firstWallet || !$firstWallet->currency) {
                 throw new \Exception("No wallet or currency found.");
            }
            $currencyCode = $firstWallet->currency->code;
        }

        $transactionsQuery = GoldTransaction::whereIn('wallet_id', $walletIds);

        if ($period === 'monthly') {
            $transactionsQuery->where('transaction_date', '>=', Carbon::now()->startOfMonth());
        } elseif ($period === 'yearly') {
            $transactionsQuery->where('transaction_date', '>=', Carbon::now()->startOfYear());
        }

        $transactions = $transactionsQuery->orderBy('transaction_date', 'desc')->get();

        $stats = [
            'total_wallets' => count($walletIds),
            'total_grams' => GoldWallet::whereIn('id', $walletIds)->sum('balance_grams'),
            'total_invested' => GoldWallet::whereIn('id', $walletIds)->sum('balance_amount'),
            'total_buys' => $transactions->where('type', 'buy')->count(),
            'total_sells' => $transactions->where('type', 'sell')->count(),
        ];

        $pdf = Pdf::loadView('gold_savers.reports.investment', [
            'user' => $user,
            'period' => $period,
            'walletName' => $walletName,
            'stats' => $stats,
            'transactions' => $transactions,
            'currency' => $currencyCode,
        ]);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, "investment-report-".now()->format('Y-m-d').".pdf");
    }
}

