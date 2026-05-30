<?php

namespace Modules\CRM\Infrastructure\Queue;

use App\Models\UserSubscription;
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
            $hasActiveAddon = UserSubscription::where('tenant_id', $job->tenantId)
                ->where('module_id', $this->addonId)
                ->where('status', 'active')
                ->exists();

            if (!$hasActiveAddon) {
                Log::warning("Queue Addon Guard: Dropped job " . get_class($job) . " because tenant {$job->tenantId} lacks active {$this->addonId} addon.");
                // Drop the job silently without retrying (it's unpaid)
                return;
            }
        }

        return $next($job);
    }
}
