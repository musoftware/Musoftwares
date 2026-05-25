<?php

namespace Modules\Booking\app\Features\OnlinePage\Services;

use Modules\Booking\Models\Booking;
use Illuminate\Support\Carbon;

class BookingAvailabilityResolver
{
    /**
     * Checks if a resource/staff member is available at a specific time
     */
    public function isAvailable(int $tenantId, int $resourceId, Carbon $startTime, int $durationMinutes): bool
    {
        $endTime = $startTime->copy()->addMinutes($durationMinutes);

        // Check against existing bookings for this resource
        // This query locks conceptually but practically we do pessimistic locking during reservation
        $conflict = Booking::where('tenant_id', $tenantId)
            ->where('resource_id', $resourceId)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('starts_at', [$startTime, $endTime])
                      ->orWhereBetween('ends_at', [$startTime, $endTime]);
            })
            ->exists();

        // Also check staff working hours here

        return !$conflict;
    }
}
