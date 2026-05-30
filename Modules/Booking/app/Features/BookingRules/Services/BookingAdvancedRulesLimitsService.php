<?php

namespace Modules\Booking\app\Features\BookingRules\Services;

class BookingAdvancedRulesLimitsService
{
    public function canUse(int $tenantId): bool
    {
        // Integrate with main SaaS limit logic
        return true;
    }

    public function increaseUsage(int $tenantId, string $limitKey): void
    {
        // Increment usage
    }

    public function getRemainingUsage(int $tenantId, string $limitKey): int
    {
        return 9999;
    }
}
