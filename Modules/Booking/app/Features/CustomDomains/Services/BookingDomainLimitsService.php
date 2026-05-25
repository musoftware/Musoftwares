<?php

namespace Modules\Booking\app\Features\CustomDomains\Services;

use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;

class BookingDomainLimitsService
{
    /**
     * Check if tenant can add another custom domain.
     *
     * @param int $tenantId
     * @return bool
     */
    public function canAddCustomDomain(int $tenantId): bool
    {
        // Assuming tenant() helper or similar is available to get features
        $tenant = app('currentTenant'); // Example, adjust based on actual multi-tenant implementation
        
        if (!$tenant || !$tenant->hasFeature('booking.custom_domain')) {
            return false;
        }

        $maxDomains = $tenant->featureValue('max_custom_domains') ?? 1;
        $currentCount = BookingCustomDomain::where('tenant_id', $tenantId)->count();

        return $currentCount < $maxDomains;
    }
}
