<?php

namespace Modules\Booking\app\Features\CustomDomains\Repositories;

use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;

class CustomDomainRepository
{
    /**
     * Get the custom domain for the current tenant.
     */
    public function getSettings()
    {
        $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
        
        return BookingCustomDomain::where('tenant_id', $tenantId)->first();
    }

    /**
     * Get pending domains for verification jobs.
     */
    public function getPendingDomains($limit = 50)
    {
        return BookingCustomDomain::withoutGlobalScope('tenant')
            ->where('status', 'pending')
            ->take($limit)
            ->get();
    }

    /**
     * Find an active domain for public routing.
     */
    public function findActiveDomain(string $domain): ?BookingCustomDomain
    {
        return BookingCustomDomain::withoutGlobalScope('tenant')
            ->where('domain', $domain)
            ->where('status', 'verified')
            ->first();
    }

    /**
     * Delete the current tenant's custom domain
     */
    public function delete()
    {
        $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
        return BookingCustomDomain::withoutGlobalScope('tenant')->where('tenant_id', $tenantId)->delete();
    }
}
