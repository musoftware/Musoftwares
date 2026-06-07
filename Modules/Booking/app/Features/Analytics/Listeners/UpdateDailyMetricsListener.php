<?php

namespace Modules\Booking\Features\Analytics\Listeners;

use Modules\Booking\Events\BookingStatusChanged;
use Modules\Booking\Models\BookingDailyMetric;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateDailyMetricsListener implements ShouldQueue
{
    public function handle(BookingStatusChanged $event)
    {
        $booking = $event->booking;
        $tenantId = $booking->eventType->user->tenant_id ?? null;

        if (!$tenantId) {
            return;
        }

        $date = $booking->starts_at->format('Y-m-d');
        $currency = $booking->currency ?? null;

        // Use atomic increment/decrement to prevent race conditions during concurrent status updates
        $metric = BookingDailyMetric::firstOrCreate([
            'tenant_id' => $tenantId,
            'date' => $date,
            'currency' => $currency,
        ]);

        // Logic based on the new status
        // Note: For a truly precise metric, we'd need to know the OLD status to decrement the old bucket,
        // but for this MVP, we assume status is moving progressively forward (pending -> confirmed -> completed)
        
        if ($booking->status === 'confirmed') {
            $metric->increment('total_bookings');
            if ($booking->payment_status === 'paid') {
                $metric->increment('total_revenue', $booking->price);
            }
        } elseif ($booking->status === 'completed') {
            $metric->increment('completed_bookings');
        } elseif ($booking->status === 'cancelled') {
            $metric->increment('cancelled_bookings');
        } elseif ($booking->status === 'no_show') {
            $metric->increment('no_show_bookings');
        }
    }
}

