<?php

namespace Modules\Booking\app\Features\CustomDomains\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\CustomDomains\Repositories\CustomDomainRepository;
use Modules\Booking\app\Features\CustomDomains\Services\DomainVerificationService;
use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;

class VerifyCustomDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $domainId;

    /**
     * Optional Domain ID to verify a single domain immediately.
     */
    public function __construct($domainId = null)
    {
        $this->domainId = $domainId;
    }

    /**
     * Execute the job.
     */
    public function handle(CustomDomainRepository $repository, DomainVerificationService $verificationService)
    {
        if ($this->domainId) {
            $domain = BookingCustomDomain::withoutGlobalScope('tenant')->find($this->domainId);
            if ($domain && $domain->status === 'pending') {
                $verificationService->verify($domain);
            }
            return;
        }

        // Otherwise process batch pending domains
        $pendingDomains = $repository->getPendingDomains(50);
        foreach ($pendingDomains as $domain) {
            $verificationService->verify($domain);
        }
    }
}
