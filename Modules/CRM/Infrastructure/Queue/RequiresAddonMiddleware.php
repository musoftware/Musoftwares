<?php

namespace Modules\CRM\Infrastructure\Queue;

use Modules\ERP\Models\Tenant;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Log;

class RequiresAddonMiddleware
{
    protected string $addonId;

    public function __construct(string $addonId)
    {
        $this->addonId = $addonId;
    }

    public function handle($job, $next)
    {
        // Check if the tenant (assuming the job has a tenantId property) has an active subscription.
        // We bypass this strict check if the job doesn't declare a tenantId to avoid crashing global jobs,
        // but for CRM tenant jobs, they must declare it.
        if (property_exists($job, 'tenantId')) {
            $tenant = Tenant::find($job->tenantId);

            if (!$tenant || !$tenant->user) {
                Log::warning("Queue Addon Guard: Dropped job " . get_class($job) . " because tenant {$job->tenantId} is invalid or has no owner.");
                return;
            }

            $subscriptionService = app(SubscriptionService::class);
            $hasActiveAddon = $subscriptionService->hasActiveSubscription($tenant->user, $this->addonId);

            if (!$hasActiveAddon) {
                Log::warning("Queue Addon Guard: Dropped job " . get_class($job) . " because tenant {$job->tenantId} lacks active {$this->addonId} addon.");
                // Drop the job silently without retrying (it's unpaid)
                return;
            }
        }

        return $next($job);
    }
}
