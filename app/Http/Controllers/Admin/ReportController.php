<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function pnl(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        // Income breakdown
        $incomeBreakdownRaw = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.type', 'revenue')
            ->whereBetween('journal_entries.date', [$from, $to])
            ->select('journal_entries.reference_type as source', DB::raw('SUM(journal_entry_lines.business_amount) as total'))
            ->groupBy('journal_entries.reference_type')
            ->get();

        $incomeBreakdown = [
            'Invoice payments' => 0,
            'Marketplace comm' => 0,
            'Subscriptions' => 0,
            'Recurring income' => 0,
            'Point purchases' => 0,
        ];

        $totalIncome = 0;
        foreach ($incomeBreakdownRaw as $row) {
            $source = $row->source ?: 'Other';

            // Map generic reference types to requested breakdown keys where possible
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

        // Expense breakdown
        $expenseBreakdownRaw = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.type', 'expense')
            ->whereBetween('journal_entries.date', [$from, $to])
            ->select('journal_entries.reference_type as type', DB::raw('SUM(journal_entry_lines.business_amount) as total'))
            ->groupBy('journal_entries.reference_type')
            ->get();

        $expenseBreakdown = [
            'Invoice costs' => 0,
            'Referral earnings' => 0,
            'Withdrawals paid' => 0,
            'Recurring expenses' => 0,
        ];

        $totalExpenses = 0;
        foreach ($expenseBreakdownRaw as $row) {
            $type = $row->type ?: 'Other';

            // Map generic types
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

        // Tenant stats using business_amount
        $tenantStats = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entry_lines.journal_entry_id', '=', 'journal_entries.id')
            ->join('accounts', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('ledgers', 'accounts.ledger_id', '=', 'ledgers.id')
            ->where('ledgers.type', 'revenue')
            ->whereBetween('journal_entries.date', [$from, $to])
            // Invoices reference_id holds the invoice ID which we map to tenant
            ->join('invoices', 'journal_entries.reference_id', '=', 'invoices.id')
            ->where('journal_entries.reference_type', 'invoice')
            ->join('tenants', 'invoices.tenant_id', '=', 'tenants.id')
            ->select('tenants.id', 'tenants.name as tenant_name', DB::raw('SUM(journal_entry_lines.business_amount) as revenue'))
            ->groupBy('tenants.id', 'tenants.name')
            ->get();

        return Inertia::render('Admin/Reports/PnL', [
            'filters' => [
                'from' => $from,
                'to' => $to,
            ],
            'incomeBreakdown' => $incomeBreakdown,
            'totalIncome' => $totalIncome,
            'expenseBreakdown' => $expenseBreakdown,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalIncome - $totalExpenses,
            'tenantStats' => $tenantStats,
        ]);
    }
}
