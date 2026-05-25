<?php

namespace Modules\Booking\app\Features\WaReminders\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\WaReminders\Models\WaSchedule;

class ReminderSchedulerService
{
    /**
     * Generate the reminder schedule for a newly created booking based on tenant rules.
     */
    public function scheduleForBooking(Booking $booking)
    {
        // In a real scenario, you'd fetch the tenant's rules from the DB.
        // For example: 24h_before, 2h_before.
        
        // Example: Create a 24-hour reminder schedule
        $twentyFourHoursBefore = $booking->starts_at->subHours(24);

        if (now()->lessThan($twentyFourHoursBefore)) {
            WaSchedule::create([
                'tenant_id' => $booking->tenant_id,
                'booking_id' => $booking->id,
                'trigger_type' => '24_hours_before',
                'scheduled_at' => $twentyFourHoursBefore,
                'status' => 'pending'
            ]);
        }
        
        // Maybe schedule an immediate confirmation
        WaSchedule::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'trigger_type' => 'booking_confirmation',
            'scheduled_at' => now(), // immediate
            'status' => 'pending'
        ]);
    }
}
