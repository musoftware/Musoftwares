<?php

namespace Modules\Booking\app\Features\CustomDomains\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;
use Modules\Booking\app\Features\CustomDomains\Services\DomainVerificationService;

class VerifyCustomDomainDns implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $domain;

    /**
     * Create a new job instance.
     */
    public function __construct(BookingCustomDomain $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Execute the job.
     */
    public function handle(DomainVerificationService $verificationService): void
    {
        if ($this->domain->status !== 'verified') {
            $verificationService->verifyOwnership($this->domain);
        }
    }
}
