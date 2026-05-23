<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;
use App\Models\WalletTransaction;
use App\Models\UserWithdrawal;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

/**
 * Admin dashboard with comprehensive analytics.
 * Recovered from old project: Admin\DashboardController + RevenueChartController
 * Modernized: All data passed as Inertia props, no hardcoded mock data.
 */
class DashboardController extends Controller
{
    public function index()
    {
        // ── Core Metrics ─────────────────────────────────────────
        $totalClients = User::where('role', 'client')->count();
        $activeTenants = Tenant::where('status', 'active')->count();

        // Revenue this month (try ledger first, fallback to invoices)
        $revenueThisMonth = $this->getRevenueThisMonth();
        $revenueLastMonth = $this->getRevenueLastMonth();

        // Growth calculations recovered from old project: DashboardController::index()
        $recentClients = User::where('role', 'client')
            ->where('created_at', '>=', now()->subDays(30))->count();
        $priorClients = User::where('role', 'client')
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();
        $clientsGrowth = $priorClients > 0
            ? round((($recentClients - $priorClients) / $priorClients) * 100, 1)
            : ($recentClients > 0 ? 100 : null);

        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : null);

        // ── Pending Withdrawals ──────────────────────────────────
        $pendingWithdrawals = UserWithdrawal::whereIn('status', ['pending', 'approved'])->count();
        $pendingWithdrawalAmount = (float) UserWithdrawal::whereIn('status', ['pending', 'approved'])->sum('amount');

        // ── Monthly Revenue Chart (12 months) ────────────────────
        // Recovered from old project: RevenueChartController::months_chart()
        $revenueChartData = $this->getMonthlyRevenueChart();

        // ── Module Revenue Breakdown ─────────────────────────────
        $moduleBreakdown = $this->getModuleBreakdown();

        // ── Recent Invoices ──────────────────────────────────────
        $recentInvoices = Invoice::with(['tenantClient', 'platformClient'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($inv) => [
                'id' => $inv->id,
                'client_name' => $inv->client?->name ?? 'Unknown',
                'invoice_number' => $inv->invoice_number,
                'amount' => (float) $inv->amount,
                'currency' => $inv->amount_currency ?? 'USD',
                'status' => $inv->status,
                'created_at' => $inv->created_at?->format('M d, Y'),
            ]);

        // ── Recent Withdrawals ───────────────────────────────────
        $recentWithdrawals = UserWithdrawal::with(['user', 'payoutMethod'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'user_name' => $w->user?->name ?? 'Unknown',
                'amount' => (float) $w->amount,
                'currency' => $w->currency ?? 'USD',
                'status' => $w->status,
                'method_type' => $w->payoutMethod?->type ?? 'N/A',
                'created_at' => $w->created_at?->format('M d, Y'),
            ]);

        // ── New Tenants (recent) ─────────────────────────────────
        $newTenants = Tenant::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'owner_name' => $t->user?->name ?? 'Unknown',
                'status' => $t->status,
                'created_at' => $t->created_at?->diffForHumans() ?? 'recently',
                'initials' => strtoupper(substr($t->name, 0, 2)),
            ]);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalClients' => $totalClients,
                'activeTenants' => $activeTenants,
                'revenueThisMonth' => round($revenueThisMonth, 2),
                'revenueGrowth' => $revenueGrowth,
                'clientsGrowth' => $clientsGrowth,
                'recentClients' => $recentClients,
                'pendingWithdrawals' => $pendingWithdrawals,
                'pendingWithdrawalAmount' => round($pendingWithdrawalAmount, 2),
            ],
            'revenueChartData' => $revenueChartData,
            'moduleBreakdown' => $moduleBreakdown,
            'recentInvoices' => $recentInvoices,
            'recentWithdrawals' => $recentWithdrawals,
            'newTenants' => $newTenants,
        ]);
    }

    /**
     * Get revenue for the current month.
     * Recovered from old project: tries ledger first, then invoices.
     */
    private function getRevenueThisMonth(): float
    {
        try {
            $ledgerRevenue = DB::table('journal_entry_lines')
                ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
                ->where('ledgers.type', 'revenue')
                ->whereMonth('journal_entry_lines.created_at', now()->month)
                ->whereYear('journal_entry_lines.created_at', now()->year)
                ->sum('journal_entry_lines.business_amount');
            if ($ledgerRevenue > 0) return (float) $ledgerRevenue;
        } catch (\Exception $e) {
            // Ledger tables may not exist yet
        }

        return (float) Invoice::where('status', 'paid')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('business_amount');
    }

    /**
     * Get revenue for last month (for growth calculation).
     */
    private function getRevenueLastMonth(): float
    {
        $lastMonth = now()->subMonth();

        try {
            $ledgerRevenue = DB::table('journal_entry_lines')
                ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
                ->where('ledgers.type', 'revenue')
                ->whereMonth('journal_entry_lines.created_at', $lastMonth->month)
                ->whereYear('journal_entry_lines.created_at', $lastMonth->year)
                ->sum('journal_entry_lines.business_amount');
            if ($ledgerRevenue > 0) return (float) $ledgerRevenue;
        } catch (\Exception $e) {}

        return (float) Invoice::where('status', 'paid')
            ->whereMonth('paid_at', $lastMonth->month)
            ->whereYear('paid_at', $lastMonth->year)
            ->sum('business_amount');
    }

    /**
     * Generate 12-month revenue chart data.
     * Recovered from old project: RevenueChartController::months_chart()
     * Modernized: returns simple array for Recharts consumption.
     */
    private function getMonthlyRevenueChart(): array
    {
        $chartData = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            // Income: paid invoices
            $income = (float) Invoice::where('status', 'paid')
                ->whereMonth('paid_at', $month)
                ->whereYear('paid_at', $year)
                ->sum('business_amount');

            // Expenses: invoice costs for paid invoices
            $expenses = 0;
            try {
                $expenses = (float) DB::table('invoice_costs')
                    ->join('invoices', 'invoice_costs.invoice_id', '=', 'invoices.id')
                    ->where('invoices.status', 'paid')
                    ->whereMonth('invoices.paid_at', $month)
                    ->whereYear('invoices.paid_at', $year)
                    ->sum('invoice_costs.business_amount');
            } catch (\Exception $e) {}

            $chartData[] = [
                'name' => $monthNames[$date->month - 1],
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
            ];
        }

        return $chartData;
    }

    /**
     * Get revenue breakdown by module/source.
     * Recovered from old project: dashboard pie chart concept.
     */
    private function getModuleBreakdown(): array
    {
        $breakdown = [];

        // ERP Invoices
        $erpRevenue = (float) Invoice::where('status', 'paid')->sum('business_amount');
        if ($erpRevenue > 0) {
            $breakdown[] = ['name' => 'ERP Invoices', 'value' => round($erpRevenue, 2), 'color' => '#4f46e5'];
        }

        // Marketplace
        try {
            $marketplaceRevenue = (float) DB::table('orders')
                ->where('status', 'completed')
                ->sum('platform_fee');
            if ($marketplaceRevenue > 0) {
                $breakdown[] = ['name' => 'Marketplace', 'value' => round($marketplaceRevenue, 2), 'color' => '#06b6d4'];
            }
        } catch (\Exception $e) {}

        // Point Purchases
        try {
            $pointsRevenue = (float) DB::table('point_transactions')
                ->where('type', 'purchase')
                ->sum('amount');
            if ($pointsRevenue > 0) {
                $breakdown[] = ['name' => 'Points', 'value' => round($pointsRevenue, 2), 'color' => '#eab308'];
            }
        } catch (\Exception $e) {}

        // If no data at all, return a placeholder structure
        if (empty($breakdown)) {
            $breakdown[] = ['name' => 'No revenue data', 'value' => 0, 'color' => '#94a3b8'];
        }

        return $breakdown;
    }
}
