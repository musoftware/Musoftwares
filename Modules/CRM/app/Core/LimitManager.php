<?php

namespace Modules\CRM\app\Core;

use Modules\CRM\Models\Workspace;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Cache;

class LimitManager
{
    protected ?array $limits = null;

    /**
     * Determine if the current workspace can perform an action based on their limits.
     *
     * @param string $limitKey (e.g. 'max_leads')
     * @param int $amount Amount they want to consume (e.g. 1)
     * @return bool
     */
    public function canUse(string $limitKey, int $amount = 1): bool
    {
        $limit = $this->getLimit($limitKey);
        
        // If limit is not defined, assume infinite (or restricted, depending on business rules).
        // For standard B2B SaaS, undefined limit = 0 (restricted).
        if ($limit === null || $limit === 0) {
            return false;
        }

        // -1 can represent unlimited
        if ($limit === -1) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($limitKey);

        return ($currentUsage + $amount) <= $limit;
    }

    /**
     * Get the defined limit for the workspace's plan
     */
    public function getLimit(string $limitKey): ?int
    {
        $limits = $this->getLimits();
        return $limits[$limitKey] ?? null;
    }

    /**
     * Get all limits defined on the active subscription plan
     */
    protected function getLimits(): array
    {
        if ($this->limits !== null) {
            return $this->limits;
        }

        $workspaceId = session('crm_workspace_id');
        if (!$workspaceId) return $this->limits = [];

        $workspace = Workspace::find($workspaceId);
        if (!$workspace || !$workspace->user_id) return $this->limits = [];

        $subscription = UserSubscription::where('client_id', $workspace->user_id)
            ->whereHas('plan', fn($q) => $q->where('module', 'crm'))
            ->where('status', 'active')
            ->with('plan')
            ->first();

        // The limits could be stored in a 'limits' json column on the plan, 
        // or inside the 'features' json column as key-value pairs (e.g., 'max_leads' => 500).
        // Assuming they are in 'features' for simplicity, or a dedicated 'limits' column if it exists.
        // Let's use 'features' array since ModulePlan model has it casted.
        $this->limits = $subscription->plan->features ?? [];

        return $this->limits;
    }

    /**
     * Calculate current usage dynamically.
     * In a production app, this should be tracked incrementally in a `crm_workspace_usages` table.
     */
    protected function getCurrentUsage(string $limitKey): int
    {
        $workspaceId = session('crm_workspace_id');
        if (!$workspaceId) return 0;

        // Simple cache to prevent counting on every request (1 minute cache)
        return Cache::remember("crm_usage_{$workspaceId}_{$limitKey}", 60, function () use ($limitKey, $workspaceId) {
            switch ($limitKey) {
                case 'max_leads':
                    return \Modules\CRM\Models\Lead::where('workspace_id', $workspaceId)->count();
                case 'max_users':
                    return \DB::table('crm_workspace_users')->where('workspace_id', $workspaceId)->count();
                // Add more complex aggregations here (e.g. monthly_messages based on created_at within current month)
                default:
                    return 0;
            }
        });
    }

    public function getAllLimits(): array
    {
        return $this->getLimits();
    }

    public function flush()
    {
        $this->limits = null;
    }
}
