<?php

namespace Modules\Booking\app\Features\WhiteLabel\Services;

use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelDomain;
use Illuminate\Support\Str;

class WhiteLabelDomainResolver
{
    /**
     * Add a custom domain for a tenant and generate DNS records for verification.
     */
    public function addDomain(int $tenantId, string $domain): WhiteLabelDomain
    {
        $txtRecord = 'musoftware-verification=' . Str::random(32);

        return WhiteLabelDomain::firstOrCreate(
            ['tenant_id' => $tenantId, 'domain' => $domain],
            [
                'status' => 'pending',
                'txt_record' => $txtRecord,
                'ssl_status' => 'pending',
            ]
        );
    }

    /**
     * Resolve a tenant ID from a domain.
     * Caches the resolution for fast lookups.
     */
    public function resolveTenantFromDomain(string $domain): ?int
    {
        return \Illuminate\Support\Facades\Cache::remember("domain_resolver:{$domain}", 3600, function () use ($domain) {
            $record = WhiteLabelDomain::where('domain', $domain)->where('status', 'active')->first();
            return $record ? $record->tenant_id : null;
        });
    }
}
