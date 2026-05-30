<?php

namespace Modules\CRM\app\Core;

use Modules\CRM\Models\Workspace;
use App\Models\UserSubscription;
use Modules\CRM\Infrastructure\Capabilities\EntitlementEngine;

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
        return app(EntitlementEngine::class)->has($feature);
    }

    /**
     * Get all active features for the current workspace based on their billing plan.
     */
    protected function getActiveFeatures(): array
    {
        return app(EntitlementEngine::class)->getActiveEntitlements();
    }

    /**
     * Get features for a specific user by querying their active subscriptions.
     */
    protected function getFeaturesForUser(\App\Models\User $user): array
    {
        return app(EntitlementEngine::class)->getForUser($user);
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
        return app(EntitlementEngine::class)->getForUser($user);
    }

    /**
     * Clear the cached features (useful for testing or changing workspaces)
     */
    public function flush()
    {
        app(EntitlementEngine::class)->flush();
    }
}
