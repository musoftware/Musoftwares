<?php

namespace App\Services;

use App\Models\User;
use Modules\ERP\Models\Tenant;
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
        $activeTenants = Tenant::where('status', 'active')->count();

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
            'totalUsers' => $totalUsers,
            'totalClients' => $totalClients,
            'activeTenants' => $activeTenants,
            'revenueThisMonth' => round($revenueThisMonth, 2),
            'revenueGrowth' => $revenueGrowth,
            'monthlyExpenses' => round($monthlyExpenses, 2),
            'pendingPayments' => round($pendingPayments, 2),
            'bookingPrice' => round($bookingPrice, 2),
            'bookingRatePerHour' => round($bookingRatePerHour, 2),
            'businessCurrency' => $businessCurrencyName,
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
        $whatsappUsers = User::where('whatsapp_balance_egp', '>', 0)->count();
        $totalWhatsappBalance = User::sum('whatsapp_balance_egp');

        return [
            'database' => 'Connected',
            'serverLoad' => function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A',
            'diskUsage' => function_exists('disk_free_space') ? round((disk_free_space("/") / max(disk_total_space("/"), 1)) * 100, 1) . '% free' : 'N/A',
            'memoryUsage' => round(memory_get_usage() / 1048576, 2) . ' MB',
            
            'whatsappUsers' => $whatsappUsers,
            'totalWhatsappBalance' => round($totalWhatsappBalance, 2),
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
}
