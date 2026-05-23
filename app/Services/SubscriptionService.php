<?php

namespace App\Services;

use App\Models\User;
use App\Models\PlatformPlan;
use App\Models\PlatformSubscription;
use App\Models\PlatformServiceItem;
use Carbon\Carbon;

class SubscriptionService
{
    /**
     * Get the user's active plan (if any).
     */
    public function getActiveSubscription(User $user, ?string $module = null)
    {
        if ($user->hasSubscription()) {
            return $user->plan;
        }
        return null;
    }

    /**
     * Check if a user has active access to a module.
     */
    public function hasActiveSubscription(User $user, string $module): bool
    {
        // Admins have access to everything
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasSubscription();
    }

    /**
     * Check if a user has active access to a specific tool.
     */
    public function hasAccessToTool(User $user, string $toolSlug): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasSubscription();
    }

    /**
     * Check if user has ANY active platform subscription.
     */
    public function hasAnySubscription(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $user->hasSubscription();
    }

    /**
     * Calculate the price for a custom plan given selected item slugs.
     */
    public function calculateCustomPrice(array $itemSlugs, string $cycle = 'monthly'): array
    {
        // Legacy system didn't have dynamic custom tool pricing like this.
        return [
            'total'     => 0,
            'cycle'     => $cycle,
            'breakdown' => [],
        ];
    }

    /**
     * Get subscription limits for a user and module.
     */
    public function getLimits(User $user, string $module): array
    {
        if ($user->hasRole('admin')) {
            return [
                'projects'     => -1,
                'invoices'     => -1,
                'tasks'        => -1,
                'team_members' => -1,
            ];
        }

        if (!$user->hasSubscription()) {
            if ($module === 'freelance') {
                return ['connects' => 20, 'commission_rate' => 10.0];
            }
            if ($module === 'erp') {
                return ['projects' => -1, 'invoices' => -1, 'tasks' => -1, 'team_members' => -1];
            }
            return ['projects' => 0, 'invoices' => 0, 'tasks' => 0, 'team_members' => 0];
        }

        if ($module === 'erp') {
            return ['projects' => -1, 'invoices' => -1, 'tasks' => -1, 'team_members' => 10];
        }

        if ($module === 'freelance') {
            return ['connects' => 120, 'commission_rate' => 5.0];
        }

        return [];
    }

    /**
     * Enforce a feature limit before action.
     */
    public function isWithinLimit(User $user, string $module, string $limitKey, int $currentCount): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $limits = $this->getLimits($user, $module);

        if (!isset($limits[$limitKey])) {
            return true;
        }

        $maxVal = $limits[$limitKey];

        if ($maxVal === -1) {
            return true;
        }

        return $currentCount < $maxVal;
    }
}
