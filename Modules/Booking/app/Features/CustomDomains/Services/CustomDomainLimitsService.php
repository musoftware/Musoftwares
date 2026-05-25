<?php

namespace Modules\Booking\app\Features\CustomDomains\Services;

class CustomDomainLimitsService
{
    /**
     * Check if the tenant is allowed to have a custom domain.
     */
    public function canUse(int $tenantId): bool
    {
        return feature('booking-custom-domain', $tenantId);
    }
}
