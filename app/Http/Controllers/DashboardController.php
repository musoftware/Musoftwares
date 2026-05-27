<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Ticket;
use App\Models\UserReferralRequestWithdraw;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\RecurringEntry;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return $this->adminDashboard($user);
        }

        return $this->clientDashboard($user);
    }

    /**
     * Client/User dashboard with real financial data.
     * Recovered from old project: DashboardController::clientDashboard()
     */
    private function clientDashboard($user)
    {
        // ── Wallet Data ──────────────────────────────────────────
        $walletBalance = (float) ($user->user_balance ?? 0);
        $earnedBalance = (float) ($user->pending_commission ?? 0);

        // ── Pending Invoices (Platform/Admin) ────────
        $pendingInvoices = collect();
        $unpaidAmount = 0;
        $unpaidCount = 0;

        $unpaidQuery = $user->invoices()
            ->whereIn('status', ['unpaid', 'partially_paid']);

        $pendingInvoices = (clone $unpaidQuery)
            ->orderBy('id', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($invoice) {
                $currencyObj = \App\Models\Currency::find($invoice->currency);
                return [
                    'id' => $invoice->id,
                    'dbId' => $invoice->id,
                    'date' => $invoice->created_at?->format('M d, Y') ?? '-',
                    'amount' => round($invoice->unpaid_total(), 2),
                    'status' => 'due',
                    'description' => 'Invoice #' . $invoice->id,
                    'currency' => $currencyObj ? $currencyObj->currency : 'USD',
                ];
            });

        $unpaidCount = $unpaidQuery->count();
        $unpaidAmount = round($user->unpaid_invoices_amount(), 2);

        // ── Recent Transactions ──────────────────────────────────
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($txn) {
                $isCredit = in_array($txn->type, ['received', 'earned']);
                return [
                    'id' => $txn->id,
                    'date' => $txn->created_at?->format('M d, Y') ?? '-',
                    'type' => $isCredit ? 'deposit' : 'withdrawal',
                    'amount' => $isCredit ? (float) $txn->amount : -1 * (float) $txn->amount,
                    'method' => ucwords(str_replace('_', ' ', $txn->reason ?? 'Wallet')),
                ];
            });

        // ── Support Tickets ──────────────────────────────────────
        $openTicketsCount = Ticket::where('user_id', $user->id)
            ->where('ticket_status', '!=', 'resolved')
            ->count();

        // ── Pending Withdrawals ──────────────────────────────────
        $pendingWithdrawals = UserReferralRequestWithdraw::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // ── Points Balance ───────────────────────────────────────
        $pointsBalance = $user->points_balance ?? 0;

        // ── Active Subscriptions & Total Monthly ────────────────
        $activeSubscriptions = DB::table('user_subscriptions')
            ->where('client_id', $user->id)
            ->where('status', 'active')
            ->count();

        $erpMonthly = DB::table('user_subscriptions')
            ->join('module_plans', 'user_subscriptions.plan_id', '=', 'module_plans.id')
            ->where('user_subscriptions.client_id', $user->id)
            ->where('user_subscriptions.status', 'active')
            ->sum('module_plans.price');

        $toolsMonthly = 0;
        try {
            $toolsMonthly = DB::table('tool_subscriptions')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->sum('amount_paid');
        } catch (\Throwable $e) {
            // Table may not exist yet
        }

        $totalMonthlySubscription = (float) $erpMonthly + (float) $toolsMonthly;

        // ── Chart Data (Wallet 6 Months) ─────────────────────────
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $chartTransactions = Transaction::where('user_id', $user->id)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%b') as month"),
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as sort_month"),
                'type',
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('month', 'sort_month', 'type')
            ->orderBy('sort_month')
            ->get();

        $chartDataRaw = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i)->format('b');
            $chartDataRaw[$m] = ['month' => $m, 'deposit' => 0, 'withdrawal' => 0];
        }

        foreach ($chartTransactions as $txn) {
            if (isset($chartDataRaw[$txn->month])) {
                if ($txn->type === 'credit') {
                    $chartDataRaw[$txn->month]['deposit'] = (float) $txn->total;
                } else {
                    $chartDataRaw[$txn->month]['withdrawal'] = (float) $txn->total;
                }
            }
        }
        $chartData = array_values($chartDataRaw);

        // ── Build stats ──────────────────────────────────────────
        $stats = [
            'walletBalance'       => $walletBalance,
            'earnedBalance'       => $earnedBalance,
            'pointsBalance'       => $pointsBalance,
            'unpaidInvoices'      => $unpaidCount,
            'unpaidAmount'        => $unpaidAmount,
            'activeSubscriptions' => max($activeSubscriptions, 0),
            'totalMonthlySubscription' => $totalMonthlySubscription,
            'openTickets'         => $openTicketsCount,
            'pendingWithdrawals'  => $pendingWithdrawals,
            'currency'            => $user->currency_name(),
        ];

        // ── Active Tool Licenses (Marketplace) ──────────────────
        $activeToolLicenses = [];
        try {
            $activeToolLicenses = \Modules\Tools\Models\ToolLicense::where('user_id', $user->id)
                ->where('status', 'active')
                ->limit(4)
                ->get()
                ->map(fn ($lic) => [
                    'license_key'    => $lic->license_key,
                    'expires_at'     => $lic->expires_at?->format('M d, Y'),
                    'tool'           => [
                        'slug'     => $lic->tool?->slug ?? '',
                        'title'    => $lic->tool?->title ?? 'Unknown Tool',
                        'icon_url' => $lic->tool?->icon_url,
                    ],
                ])
                ->toArray();
        } catch (\Throwable $e) {
            // Table may not exist in all environments — fail gracefully
        }

        return Inertia::render('Dashboard', [
            'stats'               => $stats,
            'pendingInvoices'     => $pendingInvoices,
            'recentTransactions'  => $recentTransactions,
            'chartData'           => $chartData,
            'activeToolLicenses'  => $activeToolLicenses,
        ]);
    }

    /**
     * Admin dashboard redirects to Admin/DashboardController
     */
    private function adminDashboard($user)
    {
        return app(\App\Http\Controllers\Admin\DashboardController::class)->index();
    }
}

