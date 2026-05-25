<?php

namespace Modules\Booking\app\Features\CustomDomains\Services;

use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;
use Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainCreated;
use Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainDeleted;
use Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainPrimaryChanged;
use Illuminate\Support\Facades\DB;
use Exception;

class BookingCustomDomainService
{
    /**
     * @param int $tenantId
     * @param string $domain
     * @return BookingCustomDomain
     * @throws Exception
     */
    public function createDomain(int $tenantId, string $domain): BookingCustomDomain
    {
        // 1. Check if the domain is globally unique
        if (BookingCustomDomain::where('domain', $domain)->exists()) {
            throw new Exception("This domain is already registered.");
        }

        // 2. Check tenant limits (done via limits service, assume checked before calling)

        $customDomain = BookingCustomDomain::create([
            'tenant_id' => $tenantId,
            'domain' => strtolower(trim($domain)),
            'status' => 'pending',
            'ssl_status' => 'pending',
            'is_primary' => !BookingCustomDomain::where('tenant_id', $tenantId)->exists(),
        ]);

        event(new BookingCustomDomainCreated($customDomain));

        return $customDomain;
    }

    /**
     * @param int $domainId
     * @return bool
     */
    public function deleteDomain(int $domainId): bool
    {
        $domain = BookingCustomDomain::findOrFail($domainId);

        event(new BookingCustomDomainDeleted($domain));

        return $domain->delete();
    }

    /**
     * @param int $domainId
     * @param int $tenantId
     * @return BookingCustomDomain
     */
    public function setPrimary(int $domainId, int $tenantId): BookingCustomDomain
    {
        return DB::transaction(function () use ($domainId, $tenantId) {
            BookingCustomDomain::where('tenant_id', $tenantId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);

            $domain = BookingCustomDomain::where('tenant_id', $tenantId)
                ->where('id', $domainId)
                ->firstOrFail();

            $domain->update(['is_primary' => true]);

            event(new BookingCustomDomainPrimaryChanged($domain));

            return $domain;
        });
    }
}
