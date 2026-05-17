<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Modules\Core\Models\SupportTicket;
use Modules\Core\Models\UserWithdrawal;
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
        $wallet = $user->getWallet();
        $walletBalance = (float) ($wallet->balance ?? 0);
        $earnedBalance = (float) ($wallet->earned_balance ?? 0);

        // ── Pending Invoices (via ERP tenant client link) ────────
        $pendingInvoices = collect();
        $unpaidAmount = 0;
        $unpaidCount = 0;

        $tenantClient = $user->client;
        if ($tenantClient) {
            $pendingInvoices = Invoice::where('client_id', $tenantClient->id)
                ->whereIn('status', ['sent', 'partial'])
                ->orderBy('due_date', 'asc')
                ->limit(5)
                ->get()
                ->map(function ($invoice) {
                    $unpaid = $invoice->status === 'partial'
                        ? max(0, $invoice->amount - ($invoice->paid_amount ?? 0))
                        : $invoice->amount;
                    return [
                        'id' => $invoice->invoice_number,
                        'dbId' => $invoice->id,
                        'date' => $invoice->due_date?->format('M d, Y') ?? '-',
                        'amount' => round($unpaid, 2),
                        'status' => $invoice->due_date && $invoice->due_date->isPast() ? 'overdue' : 'due',
                        'description' => $invoice->notes ?? ('Invoice ' . $invoice->invoice_number),
                        'currency' => $invoice->amount_currency ?? 'USD',
                    ];
                });

            $unpaidStats = Invoice::where('client_id', $tenantClient->id)
                ->whereIn('status', ['sent', 'partial'])
                ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total')
                ->first();
            $unpaidCount = $unpaidStats->cnt ?? 0;
            $unpaidAmount = round($unpaidStats->total ?? 0, 2);
        }

        // ── Recent Transactions ──────────────────────────────────
        $recentTransactions = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($txn) {
                $isCredit = $txn->type === 'credit';
                return [
                    'id' => 'TXN-' . str_pad($txn->id, 3, '0', STR_PAD_LEFT),
                    'date' => $txn->created_at?->format('M d, Y') ?? '-',
                    'type' => $isCredit ? 'deposit' : 'withdrawal',
                    'amount' => $isCredit ? (float) $txn->amount : -1 * (float) $txn->amount,
                    'method' => ucwords(str_replace('_', ' ', $txn->reference_type ?? 'Wallet')),
                ];
            });

        // ── Support Tickets ──────────────────────────────────────
        $openTicketsCount = SupportTicket::where('client_id', $user->id)
            ->where('status', '!=', 'resolved')
            ->count();

        // ── Pending Withdrawals ──────────────────────────────────
        $pendingWithdrawals = UserWithdrawal::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        // ── Points Balance ───────────────────────────────────────
        $pointsBalance = $user->points_balance ?? 0;

        // ── Active Subscriptions ─────────────────────────────────
        $activeSubscriptions = DB::table('user_subscriptions')
            ->where('client_id', $user->id)
            ->where('status', 'active')
            ->count();

        // ── Build stats ──────────────────────────────────────────
        $stats = [
            'walletBalance' => $walletBalance,
            'earnedBalance' => $earnedBalance,
            'pointsBalance' => $pointsBalance,
            'unpaidInvoices' => $unpaidCount,
            'unpaidAmount' => $unpaidAmount,
            'activeSubscriptions' => max($activeSubscriptions, 0),
            'openTickets' => $openTicketsCount,
            'pendingWithdrawals' => $pendingWithdrawals,
            'currency' => $wallet->currency ?? 'USD',
        ];

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'pendingInvoices' => $pendingInvoices,
            'recentTransactions' => $recentTransactions,
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
