<?php

namespace App\Services;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Earning;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\UserReferral;
use App\Models\UserReferralRequestWithdraw;
use Illuminate\Support\Facades\DB;

/**
 * EarningAnalyzeService
 *
 * Centralizes all business logic for the admin earning analysis page.
 * Controller must NEVER contain these calculations directly.
 */
class EarningAnalyzeService
{
    /**
     * Cached currency lookup: id => ['code' => ..., 'symbol' => ...]
     * Loaded once per request to avoid N+1 on groupBy queries.
     */
    private array $currencyMap = [];

    public function __construct()
    {
        $this->currencyMap = Currency::all()->keyBy('id')->map(fn($c) => [
            'code'   => $c->currency,  // e.g. "USD"
            'symbol' => $c->symbol,    // e.g. "$"
        ])->all();
    }

    /** Returns code string for a currency_id, fallback to "#ID" */
    private function currencyCode(?int $id): string
    {
        return $this->currencyMap[$id]['code'] ?? ('#' . $id);
    }

    /** Returns symbol string for a currency_id, fallback to empty */
    private function currencySymbol(?int $id): string
    {
        return $this->currencyMap[$id]['symbol'] ?? '';
    }
    // ──────────────────────────────────────────────
    // 1. KPI STATS
    // ──────────────────────────────────────────────

    /**
     * Total unique earners (users who have at least one earning record).
     */
    public function totalEarners(): int
    {
        return Earning::whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Total earnings normalized to business currency.
     */
    public function totalEarningsBusiness(): float
    {
        return (float) Earning::total_balance();
    }

    /**
     * Pending clearing = earnings not yet moved to wallet, OVERDUE (past clearing date).
     * These need immediate attention.
     */
    public function overdueClearing(): float
    {
        $query = Earning::query()->where('convert_to_balance_on', '<', now());
        if (\Illuminate\Support\Facades\Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNull('transaction_id');
        }

        $data = $query->selectRaw('SUM(amount) as amount, currency_id, FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date')
            ->groupBy('currency_id')
            ->get();

        $amount = 0;
        $businessCurrency = CurrenciesExchange::BusinessCurrency();
        foreach ($data as $row) {
            $amount += CurrenciesExchange::RateByDate($row->avg_date, $row->amount, $row->currency_id, $businessCurrency);
        }
        return round($amount, 2);
    }

    /**
     * In-window pending = earnings not yet due (still within holding window, normal state).
     */
    public function inWindowClearing(): float
    {
        $query = Earning::query()->where('convert_to_balance_on', '>=', now());
        if (\Illuminate\Support\Facades\Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNull('transaction_id');
        }

        $data = $query->selectRaw('SUM(amount) as amount, currency_id, FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date')
            ->groupBy('currency_id')
            ->get();

        $amount = 0;
        $businessCurrency = CurrenciesExchange::BusinessCurrency();
        foreach ($data as $row) {
            $amount += CurrenciesExchange::RateByDate($row->avg_date, $row->amount, $row->currency_id, $businessCurrency);
        }
        return round($amount, 2);
    }

    /**
     * Total pending clearing (both overdue and in-window combined).
     */
    public function totalPendingClearing(): float
    {
        return (float) Earning::clearing_balance();
    }

    /**
     * Total cleared earnings (moved to wallet) in business currency.
     */
    public function clearedEarnings(): float
    {
        $query = Earning::query();
        if (\Illuminate\Support\Facades\Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNotNull('transaction_id');
        }

        $data = $query->selectRaw('SUM(amount) as amount, currency_id, FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date')
            ->groupBy('currency_id')
            ->get();

        $amount = 0;
        $businessCurrency = CurrenciesExchange::BusinessCurrency();
        foreach ($data as $row) {
            $amount += CurrenciesExchange::RateByDate($row->avg_date, $row->amount, $row->currency_id, $businessCurrency);
        }
        return round($amount, 2);
    }

    // ──────────────────────────────────────────────
    // 2. ANNUAL PERFORMANCE (YTD)
    // ──────────────────────────────────────────────

    /**
     * Platform balance as of Jan 1st this year (opening balance).
     */
    public function openingBalance(): float
    {
        return (float) Transaction::get_sum_balance(date('Y') . '-01-01');
    }

    /**
     * Platform balance as of now (closing/current balance).
     */
    public function closingBalance(): float
    {
        return (float) Transaction::get_sum_balance(date('Y-m-d', strtotime('+1 day')));
    }

    /**
     * Net growth = closing - opening.
     */
    public function netGrowth(): float
    {
        $closing = $this->closingBalance();
        $opening = $this->openingBalance();
        return round($closing - $opening, 2);
    }

    /**
     * Growth percentage vs opening balance.
     */
    public function growthPercentage(): float
    {
        $opening = $this->openingBalance();
        if ($opening <= 0) return 100.0;
        return round(($this->netGrowth() / $opening) * 100, 2);
    }

    // ──────────────────────────────────────────────
    // 3. OPERATIONAL LIQUIDITY
    // ──────────────────────────────────────────────

    /**
     * Total unpaid invoice value across all clients (business currency).
     */
    public function unpaidInvoices(): float
    {
        try {
            return (float) Invoice::UnpaidInvoicesBusiness();
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Available liquidity = floating cash − unpaid invoices.
     */
    public function availableLiquidity(): float
    {
        return round($this->closingBalance() - $this->unpaidInvoices(), 2);
    }

    // ──────────────────────────────────────────────
    // 4. SETTLEMENT & WITHDRAWALS
    // ──────────────────────────────────────────────

    /**
     * Total earning pool (all-time referral earnings in business currency).
     */
    public function totalEarningPool(): float
    {
        return (float) Earning::total_balance();
    }

    /**
     * Total amount already paid out via approved withdrawal requests.
     */
    public function withdrawnBalance(): float
    {
        try {
            return (float) UserReferralRequestWithdraw::withdrawed_balance();
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Ready for withdrawal = total pool − withdrawn − clearing (pending).
     */
    public function readyForWithdrawal(): float
    {
        $ready = $this->totalEarningPool() - $this->withdrawnBalance() - $this->totalPendingClearing();
        return round(max(0, $ready), 2);
    }

    /**
     * Effective payout ratio as percentage.
     */
    public function payoutRatio(): float
    {
        $total = $this->totalEarningPool();
        if ($total <= 0) return 0.0;
        return round(($this->withdrawnBalance() / $total) * 100, 2);
    }

    /**
     * Clearing date range [start_date, end_date] or nulls.
     */
    public function clearingDateRange(): array
    {
        return [
            'start' => Earning::clearing_start_date(),
            'end'   => Earning::clearing_last_date(),
        ];
    }

    // ──────────────────────────────────────────────
    // 5. REFERRAL FUNNEL
    // ──────────────────────────────────────────────

    public function referralViews(): int
    {
        return (int) UserReferral::TotalViews();
    }

    public function referralRegisters(): int
    {
        return (int) UserReferral::TotalRegisters();
    }

    // ──────────────────────────────────────────────
    // 6. MONTHLY TREND (normalized to business currency)
    // ──────────────────────────────────────────────

    /**
     * Monthly earning totals for last 12 months, normalized to business currency.
     * Groups by month and converts each currency row to business currency.
     */
    public function monthlyTrend(): array
    {
        $businessCurrency = CurrenciesExchange::BusinessCurrency();

        $rows = Earning::selectRaw(
            "DATE_FORMAT(created_at, '%Y-%m') as month,
             currency_id,
             SUM(amount) as total,
             FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date"
        )
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->groupBy('month', 'currency_id')
            ->orderBy('month')
            ->get();

        // Aggregate per month after currency conversion
        $months = [];
        foreach ($rows as $row) {
            $converted = CurrenciesExchange::RateByDate($row->avg_date, $row->total, $row->currency_id, $businessCurrency);
            $months[$row->month] = ($months[$row->month] ?? 0) + $converted;
        }

        return collect($months)->map(fn($total, $month) => [
            'month' => $month,
            'total' => round((float) $total, 2),
        ])->values()->all();
    }

    // ──────────────────────────────────────────────
    // 7. TOP EARNERS
    // ──────────────────────────────────────────────

    public function topEarners(int $limit = 20): array
    {
        $businessCurrency = CurrenciesExchange::BusinessCurrency();

        return Earning::selectRaw(
            'user_id,
             SUM(amount) as total_earned,
             COUNT(*) as referral_count,
             currency_id,
             FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date'
        )
            ->whereNotNull('user_id')
            ->with('user:id,name,email')
            ->groupBy('user_id', 'currency_id')
            ->orderByDesc('total_earned')
            ->limit($limit * 5) // over-fetch to collapse currencies
            ->get()
            ->groupBy('user_id')
            ->map(function ($rows) use ($businessCurrency) {
                $first = $rows->first();
                $totalBusiness = $rows->sum(fn($r) =>
                    CurrenciesExchange::RateByDate($r->avg_date, $r->total_earned, $r->currency_id, $businessCurrency)
                );
                return [
                    'user_id'        => $first->user_id,
                    'name'           => $first->user?->name ?? 'Deleted User',
                    'email'          => $first->user?->email ?? '—',
                    'total_earned'   => round((float) $totalBusiness, 2),
                    'referral_count' => (int) $rows->sum('referral_count'),
                ];
            })
            ->sortByDesc('total_earned')
            ->take($limit)
            ->values()
            ->all();
    }

    // ──────────────────────────────────────────────
    // 8. RECENT EARNINGS
    // ──────────────────────────────────────────────

    public function recentEarnings(int $limit = 50): array
    {
        $businessCurrency = CurrenciesExchange::BusinessCurrency();

        return Earning::with([
            'user:id,name,email',
            'referred_user:id,name,email',
        ])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn($e) => [
                'id'                    => $e->id,
                'user_id'               => $e->user_id,
                'user_name'             => $e->user?->name ?? 'Deleted User',
                'user_email'            => $e->user?->email ?? '—',
                'referred_user_id'      => $e->referred_user?->id ?? null,
                'referred_user_name'    => $e->referred_user?->name ?? null,
                'amount'                => (float) $e->amount,
                'amount_business'       => round((float) CurrenciesExchange::RateToday($e->amount, $e->currency_id, $businessCurrency), 2),
                'currency_code'         => $this->currencyCode($e->currency_id),
                'currency_symbol'       => $this->currencySymbol($e->currency_id),
                'status'                => (\Illuminate\Support\Facades\Schema::hasColumn('earnings', 'transaction_id') && $e->transaction_id) ? 'cleared' : ($e->convert_to_balance_on && now()->isAfter($e->convert_to_balance_on) ? 'overdue' : 'pending'),
                'convert_to_balance_on' => $e->convert_to_balance_on,
                'created_at'            => $e->created_at,
            ])
            ->all();
    }

    // ──────────────────────────────────────────────
    // 9. CURRENCY BREAKDOWN
    // ──────────────────────────────────────────────

    public function currencyBreakdown(): array
    {
        return Earning::selectRaw('currency_id, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('currency_id')
            ->get()
            ->map(fn($row) => [
                'currency_id'     => $row->currency_id,
                'currency_code'   => $this->currencyCode($row->currency_id),
                'currency_symbol' => $this->currencySymbol($row->currency_id),
                'total'           => (float) $row->total,
                'count'           => (int) $row->count,
            ])
            ->all();
    }

    // ──────────────────────────────────────────────
    // 10. COMPILE FULL PAGE DATA
    // ──────────────────────────────────────────────

    public function pageData(): array
    {
        $closingBalance   = $this->closingBalance();
        $openingBalance   = $this->openingBalance();
        $unpaidInvoices   = $this->unpaidInvoices();
        $totalPool        = $this->totalEarningPool();
        $withdrawn        = $this->withdrawnBalance();
        $pendingClearing  = $this->totalPendingClearing();
        $overdueClearing  = $this->overdueClearing();
        $inWindow         = $this->inWindowClearing();
        $cleared          = $this->clearedEarnings();
        $dateRange        = $this->clearingDateRange();

        $bizCurrencyId = CurrenciesExchange::BusinessCurrency();

        return [
            // Business currency info (used by frontend to label all normalized amounts)
            'business_currency' => [
                'code'   => $this->currencyCode($bizCurrencyId),
                'symbol' => $this->currencySymbol($bizCurrencyId),
            ],

            // KPIs
            'stats' => [
                'total_earners'         => $this->totalEarners(),
                'total_earnings'        => $this->totalEarningsBusiness(),
                'pending_clearing'      => $pendingClearing,
                'overdue_clearing'      => $overdueClearing,
                'in_window_clearing'    => $inWindow,
                'cleared_earnings'      => $cleared,
            ],

            // Annual performance
            'annual' => [
                'opening_balance'   => round($openingBalance, 2),
                'closing_balance'   => round($closingBalance, 2),
                'net_growth'        => round($closingBalance - $openingBalance, 2),
                'growth_pct'        => $this->growthPercentage(),
            ],

            // Liquidity
            'liquidity' => [
                'floating_cash'       => round($closingBalance, 2),
                'unpaid_invoices'     => round($unpaidInvoices, 2),
                'available_liquidity' => round($closingBalance - $unpaidInvoices, 2),
            ],

            // Settlement
            'settlement' => [
                'total_pool'           => round($totalPool, 2),
                'withdrawn'            => round($withdrawn, 2),
                'pending_clearing'     => round($pendingClearing, 2),
                'ready_for_withdrawal' => $this->readyForWithdrawal(),
                'payout_ratio'         => $this->payoutRatio(),
                'clearing_start'       => $dateRange['start'],
                'clearing_end'         => $dateRange['end'],
            ],

            // Referral funnel
            'referral_funnel' => [
                'total_views'     => $this->referralViews(),
                'total_registers' => $this->referralRegisters(),
            ],

            // Charts & tables
            'monthly_trend'      => $this->monthlyTrend(),
            'top_earners'        => $this->topEarners(),
            'recent_earnings'    => $this->recentEarnings(),
            'currency_breakdown' => $this->currencyBreakdown(),
        ];
    }
}

