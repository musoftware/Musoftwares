<?php

namespace Modules\CRM\Infrastructure\Capabilities;

use Modules\CRM\Models\Workspace;
use App\Models\UserSubscription;
use Modules\CRM\Infrastructure\Context\TenantContext;

class EntitlementEngine
{
    protected ?array $resolvedEntitlements = null;

    public function __construct(
        protected CapabilityRegistry $registry,
        protected TenantContext $tenantContext
    ) {}

    /**
     * Determine if the current context has access to a specific capability.
     */
    public function has(string $capability): bool
    {
        $entitlements = $this->getActiveEntitlements();
        return in_array($capability, $entitlements);
    }

    /**
     * Get all actively entitled capabilities for the current context.
     */
    public function getActiveEntitlements(): array
    {
        if ($this->resolvedEntitlements !== null) {
            return $this->resolvedEntitlements;
        }

        $user = $this->resolveUserFromContext();

        if (!$user) {
            return $this->resolvedEntitlements = [];
        }

        $rawSubscriptions = $this->getRawSubscriptionsForUser($user);
        
        // Use the DAG to filter out orphaned addons whose parents are expired/missing
        return $this->resolvedEntitlements = $this->registry->resolveEntitlements($rawSubscriptions);
    }

    /**
     * Get all active entitlements for a specific user.
     */
    public function getForUser(\App\Models\User $user): array
    {
        $rawSubscriptions = $this->getRawSubscriptionsForUser($user);
        return $this->registry->resolveEntitlements($rawSubscriptions);
    }

    /**
     * Fetch raw subscription keys from the database.
     */
    protected function getRawSubscriptionsForUser(\App\Models\User $user): array
    {
        return UserSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->pluck('object')
            ->toArray();
    }

    /**
     * Resolve the owner user based on auth or TenantContext.
     */
    protected function resolveUserFromContext(): ?\App\Models\User
    {
        // 1. If user is directly authenticated
        $user = auth()->user();
        if ($user) {
            return $user;
        }

        // 2. If ERP team member is authenticated, get their tenant owner
        if (auth('erp_team')->check()) {
            return auth('erp_team')->user()?->tenant?->user;
        }

        // 3. Fallback to TenantContext to look up the workspace owner
        $workspaceId = $this->tenantContext->getWorkspaceId();
        if ($workspaceId) {
            $workspace = Workspace::find($workspaceId);
            if ($workspace && $workspace->user_id) {
                return \App\Models\User::find($workspace->user_id);
            }
        }

        return null;
    }

    /**
     * Clear cached entitlements.
     */
    public function flush(): void
    {
        $this->resolvedEntitlements = null;
    }
}
