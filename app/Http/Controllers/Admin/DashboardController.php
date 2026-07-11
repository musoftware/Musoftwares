<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
            'stats' => $this->dashboardService->getCoreMetrics(),
            'operationalStats' => $this->dashboardService->getOperationalMetrics(),
            'systemHealth' => $this->dashboardService->getSystemHealth(),
            'revenueChartData' => $this->dashboardService->getMonthlyRevenueChart(),
            'moduleBreakdown' => $this->dashboardService->getModuleBreakdown(),
            'recentActivities' => $this->dashboardService->getRecentActivities(),
        ]);
    }
}
