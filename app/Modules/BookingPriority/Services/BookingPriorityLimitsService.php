<?php

namespace App\Modules\BookingPriority\Services;

class BookingPriorityLimitsService
{
    public function canUse(int $tenantId, string $feature = 'booking.priority_support'): bool
    {
        return true;
    }

    public function increaseUsage(int $tenantId, string $limitKey): void
    {
        // Increment limit counter
    }

    public function getRemainingUsage(int $tenantId, string $limitKey): int
    {
        return 9999;
    }
}
