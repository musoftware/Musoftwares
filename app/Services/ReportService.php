<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ReportService extends BaseService
{

    public function getPnlReport(string $from, string $to): array
    {
        // ── Income Breakdown ─────────────────────────────────────
        $incomeBreakdown = [
            'Invoice payments' => 0,
            'Marketplace comm' => 0,
            'Subscriptions'    => 0,
            'Recurring income' => 0,
            'Point purchases'  => 0,
        ];
        $totalIncome = 0;

        // Income from platform transactions
        $received = (float) \App\Models\Transaction::whereIn('type', ['received', 'earned'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('business_amount');
            
        $deductions = (float) \App\Models\Transaction::whereIn('type', ['refunded', 'sent'])
            ->whereBetween('created_at', [$from, $to])
            ->sum('business_amount');

        $invoiceRevenue = $received + $deductions;
        $incomeBreakdown['Invoice payments'] = $invoiceRevenue;
        $totalIncome = $invoiceRevenue;

        // ── Expense Breakdown ────────────────────────────────────
        $expenseBreakdown = [
            'Invoice costs'     => 0,
            'Referral earnings' => 0,
            'Withdrawals paid'  => 0,
            'Recurring expenses'=> 0,
        ];
        $totalExpenses = 0;

        // Expenses from platform cost transactions
        $invoiceCosts = (float) \App\Models\CostTransaction::whereBetween('created_at', [$from, $to])
            ->sum('business_amount');
        $expenseBreakdown['Invoice costs'] = $invoiceCosts;
        $totalExpenses = $invoiceCosts;

        return [
            'filters' => [
                'from' => $from,
                'to'   => $to,
            ],
            'incomeBreakdown'  => $incomeBreakdown,
            'totalIncome'      => round($totalIncome, 2),
            'expenseBreakdown' => $expenseBreakdown,
            'totalExpenses'    => round($totalExpenses, 2),
            'netProfit'        => round($totalIncome - $totalExpenses, 2),
        ];
    }
}

