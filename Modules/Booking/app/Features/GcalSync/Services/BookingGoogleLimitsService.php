<?php

namespace Modules\Booking\app\Features\GcalSync\Services;

class BookingGoogleLimitsService
{
    public function canConnectCalendar(int $tenantId): bool
    {
        if (!feature('booking.gcal_sync', $tenantId)) {
            return false;
        }

        return true;
    }

    public function increaseUsage(int $tenantId): void
    {
        // tracking
    }
}
