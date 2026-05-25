<?php

namespace Modules\Booking\app\Features\MultiBranch\Services;

class BookingBranchLimitsService
{
    /**
     * Check if the tenant has access to the multi-branch feature.
     */
    public function canUse(int $tenantId): bool
    {
        return feature('booking-multi-branch', $tenantId);
    }
}
