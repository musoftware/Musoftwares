<?php

namespace Modules\Booking\app\Features\CustomDomains\Services;

use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;
use Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainVerified;
use Modules\Booking\app\Features\CustomDomains\Events\BookingCustomDomainFailed;
use Illuminate\Support\Facades\Log;

class DomainVerificationService
{
    /**
     * Verify domain ownership via DNS TXT record.
     *
     * @param BookingCustomDomain $domain
     * @return bool
     */
    public function verifyOwnership(BookingCustomDomain $domain): bool
    {
        try {
            $domain->update(['status' => 'verifying', 'last_checked_at' => now()]);

            $records = dns_get_record($domain->domain, DNS_TXT);
            $token = 'musoftware-verification=' . $domain->verification_token;
            
            $verified = false;
            if ($records) {
                foreach ($records as $record) {
                    if (isset($record['txt']) && $record['txt'] === $token) {
                        $verified = true;
                        break;
                    }
                }
            }

            if ($verified) {
                $domain->update([
                    'status' => 'verified',
                    'verified_at' => now(),
                    'connected_at' => now(),
                ]);
                event(new BookingCustomDomainVerified($domain));
                return true;
            } else {
                $domain->update(['status' => 'failed']);
                event(new BookingCustomDomainFailed($domain));
                return false;
            }

        } catch (\Exception $e) {
            Log::error('DNS verification failed: ' . $e->getMessage());
            $domain->update(['status' => 'failed']);
            event(new BookingCustomDomainFailed($domain));
            return false;
        }
    }
}
