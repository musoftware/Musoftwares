<?php

namespace Modules\CRM\app\Core;

use Modules\CRM\Models\Workspace;
use App\Models\UserSubscription;

class FeatureManager
{
    protected ?array $activeFeatures = null;

    /**
     * Determine if the current workspace has access to a specific feature.
     *
     * @param string $feature Key of the feature (e.g. 'crm.campaigns.whatsapp')
     * @return bool
     */
    public function has(string $feature): bool
    {
        $features = $this->getActiveFeatures();
        
        return in_array($feature, $features) || isset($features[$feature]) && $features[$feature] === true;
    }

    /**
     * Get all active features for the current workspace based on their billing plan.
     */
    protected function getActiveFeatures(): array
    {
        if ($this->activeFeatures !== null) {
            return $this->activeFeatures;
        }

        $workspaceId = session('crm_workspace_id');
        if (!$workspaceId) {
            return $this->activeFeatures = [];
        }

        $workspace = Workspace::find($workspaceId);
        if (!$workspace || !$workspace->user_id) {
            return $this->activeFeatures = [];
        }

        // Find the active CRM subscription for the workspace owner
        $subscription = UserSubscription::where('client_id', $workspace->user_id)
            ->whereHas('plan', fn($q) => $q->where('module', 'crm'))
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return $this->activeFeatures = [];
        }

        $this->activeFeatures = $subscription->plan->features ?? [];

        return $this->activeFeatures;
    }

    /**
     * Get all active features (useful for frontend hydration)
     */
    public function getAll(): array
    {
        return $this->getActiveFeatures();
    }

    /**
     * Clear the cached features (useful for testing or changing workspaces)
     */
    public function flush()
    {
        $this->activeFeatures = null;
    }
}
