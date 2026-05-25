<?php

namespace Modules\Booking\app\Features\WhiteLabel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WhiteLabel\Models\WhiteLabelDomain;
use Modules\Booking\app\Features\WhiteLabel\Events\WhiteLabelDomainConnected;

class VerifyDomainDnsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $domainId;

    public function __construct(int $domainId)
    {
        $this->domainId = $domainId;
    }

    public function handle(): void
    {
        $domain = WhiteLabelDomain::find($this->domainId);
        if (!$domain || $domain->status === 'active') {
            return;
        }

        // Simulating DNS TXT record check using native dns_get_record
        // We look for our specific txt_record string
        $records = @dns_get_record($domain->domain, DNS_TXT);
        $verified = false;

        if ($records) {
            foreach ($records as $record) {
                if (isset($record['txt']) && str_contains($record['txt'], $domain->txt_record)) {
                    $verified = true;
                    break;
                }
            }
        }

        // For local development, we'll bypass this if it's a test domain
        if (app()->environment('local') && str_ends_with($domain->domain, '.test')) {
            $verified = true;
        }

        if ($verified) {
            $domain->update(['status' => 'active']);
            event(new WhiteLabelDomainConnected($domain));
        } else {
            // Optional: increment attempt count or mark as failed
            // $domain->update(['status' => 'failed']);
        }
    }
}
