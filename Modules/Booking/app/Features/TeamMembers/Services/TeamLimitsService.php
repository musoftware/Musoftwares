<?php

namespace Modules\Booking\app\Features\TeamMembers\Services;

class TeamLimitsService
{
    /**
     * Check if the tenant is allowed to add more team members.
     * The default (no addon) is 1. If feature is unlocked, maybe higher.
     */
    public function canAddMore(int $tenantId, int $currentCount): bool
    {
        // If they have the feature flag, they can add more (e.g. unlimited or higher tier).
        if (feature('booking-team-members', $tenantId)) {
            return true;
        }

        // Without the feature, they are limited to just 1 bookable member (the owner).
        return $currentCount < 1;
    }
}
