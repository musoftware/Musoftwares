<?php

namespace App\Services;

use App\Helpers\FinanceHelper;
use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItemTimer;
use App\Models\Project;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserReferralRequestWithdraw;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DashboardService extends BaseService
{
    public function getCoreMetrics(): array
    {
        return Cache::remember('admin_core_metrics', 300, function () {
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
            $bookingRateEgp = (float) FinanceHelper::calculateOverheadHourlyRate();
            $bookingRatePerHour = CurrenciesExchange::RateToday($bookingRateEgp, 2, $businessCurrencyId);

            return [
                'totalUsers' => $totalUsers,
                'totalClients' => $totalClients,
                'revenueThisMonth' => round($revenueThisMonth, 2),
                'revenueGrowth' => $revenueGrowth,
                'monthlyExpenses' => round($monthlyExpenses, 2),
                'pendingPayments' => round($pendingPayments, 2),
                'bookingPrice' => round($bookingPrice, 2),
                'bookingRatePerHour' => round($bookingRatePerHour, 2),
                'businessCurrency' => $businessCurrencyName,
            ];
        });
    }

    public function getOperationalMetrics(): array
    {
        return Cache::remember('admin_operational_metrics', 300, function () {
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
        });
    }

    public function getSystemHealth(): array
    {
        return [
            'database' => 'Connected',
            'serverLoad' => function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A',
            'diskUsage' => function_exists('disk_free_space') ? round((disk_free_space('/') / max(disk_total_space('/'), 1)) * 100, 1).'% free' : 'N/A',
            'memoryUsage' => round(memory_get_usage() / 1048576, 2).' MB',
        ];
    }

    public function getRevenueThisMonth(): float
    {
        $received = (float) Transaction::whereIn('type', ['received', 'earned'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('business_amount');

        $deductions = (float) Transaction::whereIn('type', ['refunded', 'sent'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('business_amount');

        return $received + $deductions;
    }

    public function getRevenueLastMonth(): float
    {
        $lastMonth = now()->subMonth();

        $received = (float) Transaction::whereIn('type', ['received', 'earned'])
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('business_amount');

        $deductions = (float) Transaction::whereIn('type', ['refunded', 'sent'])
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->sum('business_amount');

        return $received + $deductions;
    }

    public function getMonthlyExpenses($startDate, $endDate, $businessCurrencyId): float
    {
        return (float) CostTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->sum('business_amount');
    }

    public function getMonthlyRevenueChart(): array
    {
        $chartData = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            $received = (float) Transaction::whereIn('type', ['received', 'earned'])
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('business_amount');

            $deductions = (float) Transaction::whereIn('type', ['refunded', 'sent'])
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('business_amount');

            $income = $received + $deductions;

            $expenses = 0;
            try {
                $expenses = (float) CostTransaction::whereMonth('created_at', $month)
                    ->whereYear('created_at', $year)
                    ->sum('business_amount');
            } catch (\Exception $e) {
            }

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
        } catch (\Exception $e) {
        }

        try {
            $pointsRevenue = (float) DB::table('point_transactions')
                ->where('type', 'purchase')
                ->sum('amount');
            if ($pointsRevenue > 0) {
                $breakdown[] = ['name' => 'Points', 'value' => round($pointsRevenue, 2), 'color' => '#eab308'];
            }
        } catch (\Exception $e) {
        }

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
            ->map(fn ($t) => [
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
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at?->diffForHumans(),
            ]);

        $tickets = Ticket::with('user')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'user_name' => $t->user?->name ?? 'Unknown',
                'subject' => Str::limit($t->subject, 30),
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
            'stats' => $this->getClientStats($user),
            'recentTransactions' => $this->getRecentTransactions($user),
            'chartData' => $this->getWalletChartData($user),
            'activeToolLicenses' => $this->getActiveToolLicenses($user),
        ];
    }

    private function getClientStats(User $user): array
    {
        $walletBalance = (float) ($user->user_balance ?? 0);
        $earnedBalance = (float) ($user->pending_commission ?? 0);
        $pointsBalance = $user->points_balance ?? 0;

        $unpaidInvoices = clone $user->invoices()->whereIn('status', ['unpaid', 'partially_paid']);
        $unpaidCount = $unpaidInvoices->count();
        $unpaidAmount = round($user->unpaid_invoices_amount(true), 2);

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

        $userCurrency = Currency::find($user->currency_id);
        if (! $userCurrency) {
            throw new \Exception("User {$user->id} is missing an associated currency relation.");
        }

        $totalMonthlySubscription = $this->calculateTotalMonthlySubscription($user, $userCurrency);

        return [
            'walletBalance' => $walletBalance,
            'earnedBalance' => $earnedBalance,
            'pointsBalance' => $pointsBalance,
            'unpaidInvoices' => $unpaidCount,
            'unpaidAmount' => $unpaidAmount,
            'outstandingBalance' => round($unpaidAmount - $walletBalance, 2),
            'activeSubscriptions' => max($activeSubscriptions, 0),
            'totalMonthlySubscription' => $totalMonthlySubscription,
            'openTickets' => $openTicketsCount,
            'pendingWithdrawals' => $pendingWithdrawals,
            'currency' => $userCurrency,
        ];
    }

    private function calculateTotalMonthlySubscription(User $user, $userCurrency): float
    {
        $activeObjects = DB::table('user_subscriptions')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('object')
            ->toArray();

        $usdCurrency = Cache::remember('currency_usd', 86400, fn () => Currency::where('currency', 'USD')->first());
        if (! $usdCurrency) {
            throw new \Exception('System USD currency not found.');
        }

        $egpCurrency = Cache::remember('currency_egp', 86400, fn () => Currency::where('currency', 'EGP')->first());
        if (! $egpCurrency) {
            throw new \Exception('System EGP currency not found.');
        }

        $rate = 1.0;
        if ($usdCurrency->id != $userCurrency->id) {
            $rate = CurrenciesExchange::RateToday(1, $usdCurrency->id, $userCurrency->id) ?: 1.0;
        }

        $egpRate = CurrenciesExchange::RateToday(1, $usdCurrency->id, $egpCurrency->id) ?: (CurrenciesExchange::RateBusiness(1, $usdCurrency->id) ?: 1.0);

        $convertPrice = function ($egpPrice) use ($egpRate, $rate, $userCurrency) {
            if ($userCurrency->currency === 'EGP') {
                return (float) $egpPrice;
            }
            $usdPrice = $egpRate > 0 ? $egpPrice / $egpRate : 0;

            return (float) ($usdPrice * $rate);
        };

        $erpMonthly = 0;
        $serviceItems = app(PricingService::class)->getServiceItems($convertPrice);
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
        } catch (\Throwable $e) {
        }

        return (float) $erpMonthly + (float) $toolsMonthly;
    }

    private function getRecentTransactions(User $user)
    {
        return Transaction::with('currency')->where('user_id', $user->id)
            ->latest()
            ->limit(8)
            ->get()
            ->map(function ($txn) {
                $isCredit = in_array($txn->type, ['received', 'earned']);

                return [
                    'id' => $txn->id,
                    'date' => $txn->created_at?->format('M d, Y') ?? '-',
                    'type' => $isCredit ? 'deposit' : 'expense',
                    'amount' => $isCredit ? (float) $txn->amount : -1 * (float) $txn->amount,
                    'method' => ucwords(str_replace('_', ' ', $txn->reason ?? 'Wallet')),
                    'currency' => $txn->currency,
                ];
            });
    }

    private function getWalletChartData(User $user): array
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $chartTransactions = Transaction::where('user_id', $user->id)
            ->where('created_at', '>=', $sixMonthsAgo)
            ->whereIn('type', ['received', 'earned', 'sent', 'refunded', 'used'])
            ->get(['created_at', 'type', 'business_amount', 'amount']);

        $chartDataRaw = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = Carbon::now()->subMonths($i)->format('Y-m');
            $chartDataRaw[$m] = ['deposit' => 0.0, 'expense' => 0.0];
        }

        foreach ($chartTransactions as $txn) {
            $m = $txn->created_at->format('Y-m');
            if (isset($chartDataRaw[$m])) {
                $isCredit = in_array($txn->type, ['received', 'earned']);
                $val = abs((float) ($txn->business_amount ?? $txn->amount));

                if ($isCredit) {
                    $chartDataRaw[$m]['deposit'] += $val;
                } else {
                    $chartDataRaw[$m]['expense'] += $val;
                }
            }
        }

        $finalData = [];
        foreach ($chartDataRaw as $key => $data) {
            $date = Carbon::createFromFormat('Y-m', $key);
            $finalData[] = [
                'month' => $date->translatedFormat('M'),
                'deposit' => round($data['deposit'], 2),
                'expense' => round($data['expense'], 2),
            ];
        }

        return $finalData;
    }

    private function getActiveToolLicenses(User $user): array
    {
        return [];
    }
}
