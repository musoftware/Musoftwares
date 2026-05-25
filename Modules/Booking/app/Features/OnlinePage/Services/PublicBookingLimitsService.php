<?php

namespace Modules\Booking\app\Features\OnlinePage\Services;

class PublicBookingLimitsService
{
    public function canUse(int $tenantId): bool
    {
        if (!feature('booking.online_page', $tenantId)) {
            return false;
        }

        return $this->getRemainingUsage($tenantId) > 0;
    }

    public function increaseUsage(int $tenantId): void
    {
        // tenant()->incrementUsage('monthly_public_bookings');
    }

    public function getRemainingUsage(int $tenantId): int
    {
        // return tenant()->getRemainingUsage('monthly_public_bookings');
        return 1000;
    }
}
