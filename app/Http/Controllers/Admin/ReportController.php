<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\InvoiceCost;
use Modules\ERP\Models\ReferralEarning;
use Modules\Core\Models\UserWithdrawal;
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

        // Try ledger-based income first
        try {
            $incomeBreakdownRaw = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
                ->where('ledgers.type', 'revenue')
                ->whereBetween('journal_entries.date', [$from, $to])
                ->select('journal_entries.reference_type as source', DB::raw('SUM(journal_entry_lines.business_amount) as total'))
                ->groupBy('source')
                ->get();

            foreach ($incomeBreakdownRaw as $row) {
                $source = $row->source ?: 'Other';
                if (stripos($source, 'invoice') !== false) {
                    $incomeBreakdown['Invoice payments'] += $row->total;
                } elseif (stripos($source, 'marketplace') !== false || stripos($source, 'order') !== false) {
                    $incomeBreakdown['Marketplace comm'] += $row->total;
                } elseif (stripos($source, 'subscription') !== false) {
                    $incomeBreakdown['Subscriptions'] += $row->total;
                } elseif (stripos($source, 'recurring') !== false) {
                    $incomeBreakdown['Recurring income'] += $row->total;
                } elseif (stripos($source, 'point') !== false) {
                    $incomeBreakdown['Point purchases'] += $row->total;
                } else {
                    if (!isset($incomeBreakdown[$source])) $incomeBreakdown[$source] = 0;
                    $incomeBreakdown[$source] += $row->total;
                }
                $totalIncome += $row->total;
            }
        } catch (\Exception $e) {
            // Fallback: invoice-based income
            $invoiceRevenue = (float) Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$from, $to])
                ->sum('business_amount');
            $incomeBreakdown['Invoice payments'] = $invoiceRevenue;
            $totalIncome = $invoiceRevenue;
        }

        // ── Expense Breakdown ────────────────────────────────────
        $expenseBreakdown = [
            'Invoice costs' => 0,
            'Referral earnings' => 0,
            'Withdrawals paid' => 0,
            'Recurring expenses' => 0,
        ];
        $totalExpenses = 0;

        // Try ledger-based expenses first
        try {
            $expenseBreakdownRaw = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
                ->where('ledgers.type', 'expense')
                ->whereBetween('journal_entries.date', [$from, $to])
                ->select('journal_entries.reference_type as type', DB::raw('SUM(journal_entry_lines.business_amount) as total'))
                ->groupBy('type')
                ->get();

            foreach ($expenseBreakdownRaw as $row) {
                $type = $row->type ?: 'Other';
                if (stripos($type, 'invoice') !== false) {
                    $expenseBreakdown['Invoice costs'] += $row->total;
                } elseif (stripos($type, 'referral') !== false) {
                    $expenseBreakdown['Referral earnings'] += $row->total;
                } elseif (stripos($type, 'withdraw') !== false) {
                    $expenseBreakdown['Withdrawals paid'] += $row->total;
                } elseif (stripos($type, 'recurring') !== false) {
                    $expenseBreakdown['Recurring expenses'] += $row->total;
                } else {
                    if (!isset($expenseBreakdown[$type])) $expenseBreakdown[$type] = 0;
                    $expenseBreakdown[$type] += $row->total;
                }
                $totalExpenses += $row->total;
            }
        } catch (\Exception $e) {
            // Fallback: invoice cost-based expenses
            $invoiceCosts = (float) InvoiceCost::whereHas('invoice', function ($q) use ($from, $to) {
                $q->where('status', 'paid')->whereBetween('paid_at', [$from, $to]);
            })->sum('business_amount');
            $expenseBreakdown['Invoice costs'] = $invoiceCosts;

            $referralCosts = (float) ReferralEarning::where('status', 'pending')
                ->whereBetween('created_at', [$from, $to])
                ->sum('business_amount');
            $expenseBreakdown['Referral earnings'] = $referralCosts;

            $withdrawalCosts = (float) UserWithdrawal::where('status', 'paid')
                ->whereBetween('updated_at', [$from, $to])
                ->sum('amount');
            $expenseBreakdown['Withdrawals paid'] = $withdrawalCosts;

            $totalExpenses = $invoiceCosts + $referralCosts + $withdrawalCosts;
        }

        // ── Tenant Revenue Breakdown ─────────────────────────────
        $tenantStats = [];
        try {
            $tenantStats = DB::table('journal_entry_lines')
                ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
                ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
                ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
                ->where('ledgers.type', 'revenue')
                ->whereBetween('journal_entries.date', [$from, $to])
                ->join('invoices', 'journal_entries.reference_id', '=', 'invoices.id')
                ->where('journal_entries.reference_type', 'invoice')
                ->join('tenants', 'invoices.tenant_id', '=', 'tenants.id')
                ->select('tenants.name as tenant_name', DB::raw('SUM(journal_entry_lines.business_amount) as revenue'))
                ->groupBy('tenants.id', 'tenants.name')
                ->get();
        } catch (\Exception $e) {
            // Fallback: invoice-based tenant stats
            $tenantStats = Invoice::where('status', 'paid')
                ->whereBetween('paid_at', [$from, $to])
                ->join('tenants', 'invoices.tenant_id', '=', 'tenants.id')
                ->select('tenants.name as tenant_name', DB::raw('SUM(invoices.business_amount) as revenue'))
                ->groupBy('tenants.id', 'tenants.name')
                ->get();
        }

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
