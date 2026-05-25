<?php

namespace Modules\Booking\app\Features\CustomDomains\Services;

use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;
use Illuminate\Support\Facades\Cache;

class TenantDomainResolver
{
    /**
     * Resolve tenant ID from the host domain.
     *
     * @param string $host
     * @return int|null
     */
    public function resolveTenantByDomain(string $host): ?int
    {
        $cacheKey = "resolved_domain_tenant_{$host}";

        return Cache::remember($cacheKey, 3600, function () use ($host) {
            $domain = BookingCustomDomain::where('domain', $host)
                ->where('status', 'verified')
                ->first();

            return $domain ? $domain->tenant_id : null;
        });
    }
    
    /**
     * Clear the resolution cache for a given domain
     * 
     * @param string $host
     */
    public function clearCache(string $host): void
    {
        Cache::forget("resolved_domain_tenant_{$host}");
    }
}
