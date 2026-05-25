<?php

namespace App\Services;

use App\Models\User;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;
use App\Models\UserReferralRequestWithdraw;
use App\Models\AdminSettings;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getCoreMetrics(): array
    {
        $totalClients = User::role('client')->count();
        $activeTenants = Tenant::where('status', 'active')->count();

        $revenueThisMonth = $this->getRevenueThisMonth();
        $revenueLastMonth = $this->getRevenueLastMonth();

        $recentClients = User::role('client')
            ->where('created_at', '>=', now()->subDays(30))->count();
        $priorClients = User::role('client')
            ->whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])->count();
        
        $clientsGrowth = $priorClients > 0
            ? round((($recentClients - $priorClients) / $priorClients) * 100, 1)
            : ($recentClients > 0 ? 100 : null);

        $revenueGrowth = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : ($revenueThisMonth > 0 ? 100 : null);

        $pendingWithdrawals = UserReferralRequestWithdraw::whereIn('status', ['pending', 'approved'])->count();
        
        $pendingWithdrawalData = UserReferralRequestWithdraw::whereIn('status', ['pending', 'approved'])
            ->select('amount', 'currency_id', 'created_at')
            ->get();
            
        $pendingWithdrawalAmount = 0.0;
        $businessCurrencyId = AdminSettings::business_currency();
        foreach ($pendingWithdrawalData as $w) {
            $pendingWithdrawalAmount += (float) \App\Models\CurrenciesExchange::RateByDate(
                $w->created_at,
                $w->amount,
                $w->currency_id ?? $businessCurrencyId,
                $businessCurrencyId
            );
        }

        return [
            'totalClients' => $totalClients,
            'activeTenants' => $activeTenants,
            'revenueThisMonth' => round($revenueThisMonth, 2),
            'revenueGrowth' => $revenueGrowth,
            'clientsGrowth' => $clientsGrowth,
            'recentClients' => $recentClients,
            'pendingWithdrawals' => $pendingWithdrawals,
            'pendingWithdrawalAmount' => round($pendingWithdrawalAmount, 2),
            'businessCurrency' => AdminSettings::business_currency_name(),
        ];
    }

    public function getRevenueThisMonth(): float
    {
        return (float) \App\Models\Transaction::where('type', 'received')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('business_amount');
    }

    public function getRevenueLastMonth(): float
    {
        $lastMonth = now()->subMonth();

        return (float) \App\Models\Transaction::where('type', 'received')
            ->whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
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

            $income = (float) \App\Models\Transaction::where('type', 'received')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->sum('business_amount');

            $expenses = 0;
            try {
                $expenses = (float) \App\Models\CostTransaction::whereMonth('created_at', $month)
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

        $erpRevenue = (float) \App\Models\Transaction::where('type', 'received')->sum('business_amount');
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

    public function getRecentInvoices()
    {
        return Invoice::with(['tenantClient', 'platformClient'])
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
    }

    public function getRecentWithdrawals()
    {
        return UserReferralRequestWithdraw::with(['user', 'payoutMethod'])
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
    }

    public function getNewTenants()
    {
        return Tenant::with('user')
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
    }
}
