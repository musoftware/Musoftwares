<?php

namespace Modules\Booking\Features\Analytics\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Booking\Features\Analytics\Services\BookingAnalyticsService;
use Carbon\Carbon;
use Inertia\Inertia;

class AnalyticsDashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(BookingAnalyticsService $analyticsService)
    {
        // Enforce the SaaS feature flag
        $this->middleware('feature:booking-analytics');
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Default to last 30 days
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $summary = $this->analyticsService->getSummary($tenantId, $startDate, $endDate);

        return Inertia::render('Booking/Analytics/Dashboard', [
            'summary' => $summary,
            'dateRange' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]
        ]);
    }

    public function apiSummary(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

        $summary = $this->analyticsService->getSummary($tenantId, $startDate, $endDate);

        return response()->json([
            'data' => $summary
        ]);
    }
}
