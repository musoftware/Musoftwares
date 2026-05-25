<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\DashboardService;
use Inertia\Inertia;

/**
 * Admin dashboard with comprehensive analytics.
 * Recovered from old project: Admin\DashboardController + RevenueChartController
 * Modernized: All data passed as Inertia props, no hardcoded mock data.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats'             => $this->dashboardService->getCoreMetrics(),
            'revenueChartData'  => $this->dashboardService->getMonthlyRevenueChart(),
            'moduleBreakdown'   => $this->dashboardService->getModuleBreakdown(),
            'recentInvoices'    => $this->dashboardService->getRecentInvoices(),
            'recentWithdrawals' => $this->dashboardService->getRecentWithdrawals(),
            'newTenants'        => $this->dashboardService->getNewTenants(),
        ]);
    }


}
