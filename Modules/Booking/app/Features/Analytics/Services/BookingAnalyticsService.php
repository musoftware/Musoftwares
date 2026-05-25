<?php

namespace Modules\Booking\Features\Analytics\Services;

use Modules\Booking\Models\BookingDailyMetric;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingAnalyticsService
{
    /**
     * Retrieves an aggregated summary for a specific tenant within a date range.
     */
    public function getSummary(int $tenantId, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $metrics = BookingDailyMetric::where('tenant_id', $tenantId)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->select(
                DB::raw('SUM(total_bookings) as total_bookings'),
                DB::raw('SUM(completed_bookings) as completed_bookings'),
                DB::raw('SUM(cancelled_bookings) as cancelled_bookings'),
                DB::raw('SUM(no_show_bookings) as no_show_bookings'),
                DB::raw('SUM(total_revenue) as total_revenue'),
                'currency'
            )
            ->groupBy('currency')
            ->get();

        $totalBookings = $metrics->sum('total_bookings');
        $totalNoShows = $metrics->sum('no_show_bookings');
        
        $noShowRate = $totalBookings > 0 ? round(($totalNoShows / $totalBookings) * 100, 2) : 0;

        return [
            'total_bookings' => $totalBookings,
            'completed_bookings' => $metrics->sum('completed_bookings'),
            'cancelled_bookings' => $metrics->sum('cancelled_bookings'),
            'no_show_bookings' => $totalNoShows,
            'no_show_rate_percent' => $noShowRate,
            'revenue_by_currency' => $metrics->mapWithKeys(function ($item) {
                return [$item->currency => (float) $item->total_revenue];
            })->toArray()
        ];
    }
}
