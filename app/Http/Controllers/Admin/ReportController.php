<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Http\Requests\Admin\Report\PnlReportRequest;
use Inertia\Inertia;

use App\Services\DashboardService;

/**
 * P&L and financial reports for admin.
 * Modernized to include a comprehensive tabbed view with system metrics.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected DashboardService $dashboardService
    ) {}

    public function index(PnlReportRequest $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $pnlData = $this->reportService->getPnlReport($from, $to);
        
        $data = [
            'pnl' => $pnlData,
            'stats' => $this->dashboardService->getCoreMetrics(),
            'operationalStats' => $this->dashboardService->getOperationalMetrics(),
            'systemHealth' => $this->dashboardService->getSystemHealth(),
            'revenueChartData' => $this->dashboardService->getMonthlyRevenueChart(),
            'moduleBreakdown' => $this->dashboardService->getModuleBreakdown(),
        ];

        return Inertia::render('Admin/Reports/Index', $data);
    }
}
