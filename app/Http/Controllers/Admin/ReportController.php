<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Http\Requests\Admin\Report\PnlReportRequest;
use Inertia\Inertia;

/**
 * P&L and financial reports for admin.
 * Recovered from old project: Admin\RevenueChartController + old P&L pages.
 * Modernized: Graceful fallback when ledger tables don't exist, 
 *   uses invoice-based calculations instead.
 */
class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}
    public function pnl(PnlReportRequest $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->endOfMonth()->toDateString());

        $reportData = $this->reportService->getPnlReport($from, $to);

        return Inertia::render('Admin/Reports/PnL', $reportData);
    }
}
