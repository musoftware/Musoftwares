<?php

namespace Modules\Booking\app\Features\GroupSessions\Services;

class BookingGroupSessionLimitsService
{
    public function canCreateSession(int $tenantId): bool
    {
        if (!feature('booking.group_sessions', $tenantId)) {
            return false;
        }

        return true;
    }

    public function increaseUsage(int $tenantId): void
    {
        // tracking
    }
}
