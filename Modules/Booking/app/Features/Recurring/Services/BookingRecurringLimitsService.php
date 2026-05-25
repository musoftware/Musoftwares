<?php

namespace Modules\Booking\app\Features\Recurring\Services;

class BookingRecurringLimitsService
{
    public function canCreateSeries(int $tenantId): bool
    {
        if (!feature('booking.recurring', $tenantId)) {
            return false;
        }

        return true;
    }
}
