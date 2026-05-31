<?php

namespace Modules\CRM\Infrastructure\Queue;

use Modules\CRM\Models\Workspace;
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
        // Check if the workspace owner has an active subscription for the required addon.
        // We support both CRM workspaceId and legacy tenantId for backward compatibility.
        $workspaceId = property_exists($job, 'workspaceId') ? $job->workspaceId : null;

        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);

            if (!$workspace || !$workspace->owner) {
                Log::warning("Queue Addon Guard: Dropped job " . get_class($job) . " because workspace {$workspaceId} is invalid or has no owner.");
                return;
            }

            $subscriptionService = app(SubscriptionService::class);
            $hasActiveAddon = $subscriptionService->hasActiveSubscription($workspace->owner, $this->addonId);

            if (!$hasActiveAddon) {
                Log::warning("Queue Addon Guard: Dropped job " . get_class($job) . " because workspace {$workspaceId} lacks active {$this->addonId} addon.");
                return;
            }
        }

        return $next($job);
    }
}
