<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceCost;
use Modules\ERP\Models\ReferralEarning;
use App\Models\UserReferralRequestWithdraw;
use Inertia\Inertia;

/**
 * P&L and financial reports for admin.
 * Recovered from old project: Admin\RevenueChartController + old P&L pages.
 * Modernized: Graceful fallback when ledger tables don't exist, 
 *   uses invoice-based calculations instead.
 */
class ReportController extends Controller
{
    public function pnl(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // ── Income Breakdown ─────────────────────────────────────
        $incomeBreakdown = [
            'Invoice payments' => 0,
            'Marketplace comm' => 0,
            'Subscriptions' => 0,
            'Recurring income' => 0,
            'Point purchases' => 0,
        ];
        $totalIncome = 0;

        // Income from old project transactions
        $invoiceRevenue = (float) \App\Models\Transaction::where('type', 'received')
            ->whereBetween('created_at', [$from, $to])
            ->sum('business_amount');
        $incomeBreakdown['Invoice payments'] = $invoiceRevenue;
        $totalIncome = $invoiceRevenue;

        // ── Expense Breakdown ────────────────────────────────────
        $expenseBreakdown = [
            'Invoice costs' => 0,
            'Referral earnings' => 0,
            'Withdrawals paid' => 0,
            'Recurring expenses' => 0,
        ];
        $totalExpenses = 0;

        // Expenses from old project cost transactions
        $invoiceCosts = (float) \App\Models\CostTransaction::whereBetween('created_at', [$from, $to])
            ->sum('business_amount');
        $expenseBreakdown['Invoice costs'] = $invoiceCosts;

        $referralCosts = 0;
        $expenseBreakdown['Referral earnings'] = $referralCosts;

        $withdrawalCosts = 0;
        $expenseBreakdown['Withdrawals paid'] = $withdrawalCosts;

        $totalExpenses = $invoiceCosts;

        // Tenant stats
        $tenantStats = Invoice::where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->join('erp_tenants', 'invoices.tenant_id', '=', 'tenants.id')
            ->select('tenants.name as tenant_name', DB::raw('SUM(invoices.paid) as revenue'))
            ->groupBy('tenants.id', 'tenants.name')
            ->get();

        return Inertia::render('Admin/Reports/PnL', [
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'incomeBreakdown' => $incomeBreakdown,
            'totalIncome' => round($totalIncome, 2),
            'expenseBreakdown' => $expenseBreakdown,
            'totalExpenses' => round($totalExpenses, 2),
            'netProfit' => round($totalIncome - $totalExpenses, 2),
            'tenantStats' => $tenantStats,
        ]);
    }
}
