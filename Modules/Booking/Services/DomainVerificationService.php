<?php

namespace Modules\Booking\Services;

use Modules\Booking\Models\TenantDomain;
use Exception;
use Illuminate\Support\Facades\Log;

class DomainVerificationService
{
    /**
     * Checks if the TXT record exists for the given domain matching the token.
     */
    public function verifyDnsTxtRecord(TenantDomain $tenantDomain): bool
    {
        $domain = $tenantDomain->domain;
        $token = $tenantDomain->verification_token;

        try {
            $records = dns_get_record($domain, DNS_TXT);
            
            if (!$records) {
                return false;
            }

            foreach ($records as $record) {
                if (isset($record['txt']) && $record['txt'] === "musoftware-verification={$token}") {
                    
                    $tenantDomain->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                        'ssl_status' => 'pending', // Triggers next step in infrastructure to provision SSL
                    ]);
                    
                    return true;
                }
            }
        } catch (Exception $e) {
            Log::error("DNS verification failed for {$domain}: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Generates a new verification token for a domain.
     */
    public function generateToken(TenantDomain $tenantDomain): string
    {
        $token = bin2hex(random_bytes(16));
        $tenantDomain->update(['verification_token' => $token]);
        return $token;
    }
}
