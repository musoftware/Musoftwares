<?php

namespace App\Services;

use App\Models\User;
use App\Models\Invoice;
use App\Models\UserReferralRequestWithdraw;
use App\Models\AdminSettings;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\CostTransaction;
use App\Models\RecurringIncome;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\UserActivity;
use App\Models\CurrenciesExchange;
use App\Models\InvoiceItemTimer;
use Carbon\Carbon;

class DashboardService
{
    public function getCoreMetrics(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $businessCurrencyId = AdminSettings::business_currency();
        $businessCurrencyName = AdminSettings::business_currency_name();

        $totalUsers = User::count();
        $totalClients = User::role('client')->count();
        // Note: ERP tenant metrics are not included here — ERP manages its own analytics.

        // Revenue
        $revenueThisMonth = $this->getRevenueThisMonth();
        $revenueLastMonth = $this->getRevenueLastMonth();
        
        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : 0);

        // Expenses
        $monthlyExpenses = $this->getMonthlyExpenses($startDate, $endDate, $businessCurrencyId);
        
        // Pending Payments
        $pendingPayments = 0;
        $unpaidInvoices = Invoice::UnpaidInvoices()->get();
        foreach ($unpaidInvoices as $invoice) {
            $pendingPayments += CurrenciesExchange::RateToday(
                $invoice->unpaid,
                $invoice->currency ?? 1,
                $businessCurrencyId
            );
        }

        // Booking Price (InvoiceItemTimer)
        $bookingPrice = 0;
        if (class_exists(InvoiceItemTimer::class)) {
            $bookingPrice = InvoiceItemTimer::whereBetween('created_at', [$startDate, $endDate])
                ->sum('business_amount');
        }

        // Hourly Rate (Calculated from overhead)
        $bookingRateEgp = (float) \App\Helpers\FinanceHelper::calculateOverheadHourlyRate();
        $bookingRatePerHour = CurrenciesExchange::RateToday($bookingRateEgp, 2, $businessCurrencyId);

        return [
            'totalUsers'         => $totalUsers,
            'totalClients'       => $totalClients,
            'revenueThisMonth'   => round($revenueThisMonth, 2),
            'revenueGrowth'      => $revenueGrowth,
            'monthlyExpenses'    => round($monthlyExpenses, 2),
            'pendingPayments'    => round($pendingPayments, 2),
            'bookingPrice'       => round($bookingPrice, 2),
            'bookingRatePerHour' => round($bookingRatePerHour, 2),
            'businessCurrency'   => $businessCurrencyName,
        ];
    }

    public function getOperationalMetrics(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('archived', 0)->count();
        $completedProjects = Project::where('archived', 1)->count();
        
        $totalTasks = Task::count();
        $completedTasks = Task::where('archived', 1)->count();
        $pendingTasks = Task::where('archived', 0)->count();

        $openTickets = Ticket::where('ticket_status', '!=', 'closed')->count();
        $urgentTickets = Ticket::where('priority', 'high')->where('ticket_status', '!=', 'closed')->count();
        
        $premiumUsers = User::whereNotNull('subscription_date')->where('subscription_date', '>', now())->count();
        $activeUsers30d = UserActivity::where('activity_date', '>=', now()->subDays(30))->distinct('user_id')->count('user_id');

        return [
            'totalProjects' => $totalProjects,
            'activeProjects' => $activeProjects,
            'completedProjects' => $completedProjects,
            
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
            'pendingTasks' => $pendingTasks,

            'openTickets' => $openTickets,
            'urgentTickets' => $urgentTickets,
            
            'premiumUsers' => $premiumUsers,
            'activeUsers30d' => $activeUsers30d,
        ];
    }

    public function getSystemHealth(): array
    {
        return [
            'database' => 'Connected',
            'serverLoad' => function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A',
            'diskUsage' => function_exists('disk_free_space') ? round((disk_free_space("/") / max(disk_total_space("/"), 1)) * 100, 1) . '% free' : 'N/A',
            'memoryUsage' => round(memory_get_usage() / 1048576, 2) . ' MB',
        ];
    }

    public function getRevenueThisMonth(): float
    {
        return (float) Transaction::where('type', 'received')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('business_amount');
    }

    public function getRevenueLastMonth(): float
    {
        $lastMonth = now()->subMonth();

        return (float) Transaction::where('type', 'received')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('business_amount');
    }

    public function getMonthlyExpenses($startDate, $endDate, $businessCurrencyId): float
    {
        $costTransactionSum = CostTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->sum(function ($cost) use ($businessCurrencyId) {
                return CurrenciesExchange::RateToday(
                    $cost->amount,
                    $cost->currency ?? 1,
                    $businessCurrencyId
                );
            });

        $sentTransactions = Transaction::whereIn('type', ['sent', 'refunded'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('business_amount');

        return $costTransactionSum + $sentTransactions;
    }

    public function getMonthlyRevenueChart(): array
    {
        $chartData = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $income = (float) Transaction::where('type', 'received')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('business_amount');

            $expenses = 0;
            try {
                $expenses = (float) CostTransaction::whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->sum('business_amount');
            } catch (\Exception $e) {}

            $chartData[] = [
                'name' => $monthNames[$date->month - 1],
                'income' => round($income, 2),
                'expenses' => round($expenses, 2),
            ];
        }

        return $chartData;
    }

    public function getModuleBreakdown(): array
    {
        $breakdown = [];

        $erpRevenue = (float) Transaction::where('type', 'received')->sum('business_amount');
        if ($erpRevenue > 0) {
            $breakdown[] = ['name' => 'ERP Invoices', 'value' => round($erpRevenue, 2), 'color' => '#4f46e5'];
        }

        try {
            $marketplaceRevenue = (float) DB::table('orders')
                ->where('status', 'completed')
                ->sum('platform_fee');
            if ($marketplaceRevenue > 0) {
                $breakdown[] = ['name' => 'Marketplace', 'value' => round($marketplaceRevenue, 2), 'color' => '#06b6d4'];
            }
        } catch (\Exception $e) {}

        try {
            $pointsRevenue = (float) DB::table('point_transactions')
                ->where('type', 'purchase')
                ->sum('amount');
            if ($pointsRevenue > 0) {
                $breakdown[] = ['name' => 'Points', 'value' => round($pointsRevenue, 2), 'color' => '#eab308'];
            }
        } catch (\Exception $e) {}

        if (empty($breakdown)) {
            $breakdown[] = ['name' => 'No revenue data', 'value' => 0, 'color' => '#94a3b8'];
        }

        return $breakdown;
    }

    public function getRecentActivities(): array
    {
        $transactions = Transaction::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'user_name' => $t->user?->name ?? 'Unknown',
                'amount' => (float) $t->business_amount,
                'type' => $t->type,
                'reason' => $t->reason,
                'created_at' => $t->created_at?->diffForHumans(),
            ]);

        $users = User::latest()
            ->take(5)
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at?->diffForHumans(),
            ]);

        $tickets = Ticket::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                'user_name' => $t->user?->name ?? 'Unknown',
                'subject' => \Illuminate\Support\Str::limit($t->subject, 30),
                'status' => $t->ticket_status,
                'created_at' => $t->created_at?->diffForHumans(),
            ]);

        return [
            'transactions' => $transactions,
            'users' => $users,
            'tickets' => $tickets,
        ];
    }

    public function getClientDashboardData(User $user): array
    {
        return [
            'stats'               => $this->getClientStats($user),
            'pendingInvoices'     => $this->getPendingInvoices($user),
            'recentTransactions'  => $this->getRecentTransactions($user),
            'chartData'           => $this->getWalletChartData($user),
            'activeToolLicenses'  => $this->getActiveToolLicenses($user),
        ];
    }

    private function getClientStats(User $user): array
    {
        $walletBalance = (float) ($user->user_balance ?? 0);
        $earnedBalance = (float) ($user->pending_commission ?? 0);
        $pointsBalance = $user->points_balance ?? 0;

        $unpaidInvoices = clone $user->invoices()->whereIn('status', ['unpaid', 'partially_paid']);
        $unpaidCount = $unpaidInvoices->count();
        $unpaidAmount = round($user->unpaid_invoices_amount(), 2);

        $openTicketsCount = Ticket::where('user_id', $user->id)
            ->where('ticket_status', '!=', 'resolved')
            ->count();

        $pendingWithdrawals = UserReferralRequestWithdraw::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $activeSubscriptions = DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->count();

        $totalMonthlySubscription = $this->calculateTotalMonthlySubscription($user);

        return [
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
    }

    private function calculateTotalMonthlySubscription(User $user): float
    {
        $activeObjects = DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('object')
            ->toArray();

        $usdCurrency = \App\Models\Currency::where('currency', 'USD')->first();
        $usdCurrencyId = $usdCurrency ? $usdCurrency->id : 1;
        
        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency ? $egpCurrency->id : 1;
        
        $userCurrencyId = $user->currency_id ?: $egpCurrencyId;
        $userCurrency = \App\Models\Currency::find($userCurrencyId);
        $currencyCode = $userCurrency ? $userCurrency->currency : 'USD';
        
        $rate = 1.0;
        if ($usdCurrency && $userCurrencyId && $usdCurrency->id != $userCurrencyId) {
            $rate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrencyId);
        }

        $egpRate = 50; // Fallback
        if ($usdCurrency && $egpCurrencyId) {
            $egpRate = \App\Models\CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrencyId) ?: 50;
        }

        $convertPrice = function($egpPrice) use ($egpRate, $rate, $currencyCode) {
            if ($currencyCode === 'EGP') {
                return round($egpPrice);
            }
            $usdPrice = $egpPrice / $egpRate;
            $converted = $usdPrice * $rate;
            return psychological_price($converted);
        };

        $erpMonthly = 0;
        $serviceItems = app(\App\Services\PricingService::class)->getServiceItems($convertPrice);
        foreach ($activeObjects as $object) {
            $item = collect($serviceItems)->firstWhere('id', $object);
            if ($item) {
                $erpMonthly += $item['monthly_price'] ?? 0;
            }
        }

        $toolsMonthly = 0;
        try {
            $toolsMonthly = DB::table('tool_subscriptions')
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->sum('amount_paid');
        } catch (\Throwable $e) {}

        return (float) $erpMonthly + (float) $toolsMonthly;
    }

    private function getPendingInvoices(User $user)
    {
        return $user->invoices()
            ->whereIn('status', ['unpaid', 'partially_paid'])
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
    }

    private function getRecentTransactions(User $user)
    {
        return Transaction::where('user_id', $user->id)
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
    }

    private function getWalletChartData(User $user): array
    {
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
        
        return array_values($chartDataRaw);
    }

    private function getActiveToolLicenses(User $user): array
    {
        try {
            return \Modules\Tools\Models\ToolLicense::where('user_id', $user->id)
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
            return [];
        }
    }
}
