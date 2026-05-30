<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Services;

class BookingSmartSlotsLimitsService
{
    public function canUse(int $tenantId, string $feature = 'booking.smart_slots'): bool
    {
        return true;
    }

    public function increaseUsage(int $tenantId, string $limitKey): void
    {
        // Increment optimizations counter
    }

    public function getRemainingUsage(int $tenantId, string $limitKey): int
    {
        return 9999;
    }
}
