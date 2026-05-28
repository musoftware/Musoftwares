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
     * @param string $feature Key of the feature (e.g. 'erp-backup')
     * @return bool
     */
    public function has(string $feature): bool
    {
        $features = $this->getActiveFeatures();
        
        return in_array($feature, $features) || (isset($features[$feature]) && $features[$feature] === true);
    }

    /**
     * Get all active features for the current workspace based on their billing plan.
     */
    protected function getActiveFeatures(): array
    {
        if ($this->activeFeatures !== null) {
            return $this->activeFeatures;
        }

        // Try to resolve user from multiple guards
        $user = auth()->user();
        if (!$user && auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }

        if ($user) {
            return $this->activeFeatures = $this->getFeaturesForUser($user);
        }

        $workspaceId = session('crm_workspace_id');
        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);
            if ($workspace && $workspace->user_id) {
                $owner = \App\Models\User::find($workspace->user_id);
                if ($owner) {
                    return $this->activeFeatures = $this->getFeaturesForUser($owner);
                }
            }
        }

        return $this->activeFeatures = [];
    }

    /**
     * Get features for a specific user by querying their active subscriptions.
     */
    protected function getFeaturesForUser(\App\Models\User $user): array
    {
        return UserSubscription::where('client_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->pluck('object')
            ->toArray();
    }

    /**
     * Get all active features (useful for frontend hydration)
     */
    public function getAll(): array
    {
        return $this->getActiveFeatures();
    }

    /**
     * Get all active features for a specific user (bypasses guard resolution).
     */
    public function getAllForUser(\App\Models\User $user): array
    {
        return $this->activeFeatures = $this->getFeaturesForUser($user);
    }

    /**
     * Clear the cached features (useful for testing or changing workspaces)
     */
    public function flush()
    {
        $this->activeFeatures = null;
    }
}
