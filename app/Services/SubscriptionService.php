<?php

namespace App\Services;

use App\Models\User;
use App\Models\Plan;
use App\Models\UserSubscription;
use App\Models\Service;
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
        // Points-based modules are never free
        if (in_array($module, ['freelance', 'facebook-publisher'])) {
            return $user->hasSubscription();
        }

        // Admins and moderators have access to everything else
        if ($user->hasRole('admin') || $user->hasRole('moderator')) {
            return true;
        }

        return $user->hasSubscription();
    }

    /**
     * Check if a user has active access to a specific tool.
     */
    public function hasAccessToTool(User $user, string $toolSlug): bool
    {
        // Points-based tools are never free
        if (in_array($toolSlug, ['freelance', 'facebook-publisher'])) {
            return false;
        }

        if ($user->hasRole('admin') || $user->hasRole('moderator')) {
            return true;
        }

        if ($user->hasSubscription()) {
            return true;
        }

        $tool = collect(config('tools'))->firstWhere('slug', $toolSlug);
        if ($tool && class_exists(\Modules\Tools\Models\ToolSubscription::class)) {
            return \Modules\Tools\Models\ToolSubscription::where('user_id', $user->id)
                ->where('tool_guid', $tool['guid'])
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    /**
     * Check if user has ANY active platform subscription.
     */
    public function hasAnySubscription(User $user): bool
    {
        if ($user->hasRole('admin') || $user->hasRole('moderator')) {
            return true;
        }

        return $user->hasSubscription();
    }

    public function createSubscription(User $user, Plan $plan, string $cycle, array $customItems = []): UserSubscription
    {
        $amount = $plan->priceFor($cycle); // Assuming Plan has this method or we adjust it

        return UserSubscription::create([]);
    }

    /**
     * Calculate the price for a custom plan given selected item slugs.
     */
    public function calculateUpgradeProration(UserSubscription $current, Plan $newPlan, string $cycle): float
    {
        // Legacy system didn't have dynamic custom tool pricing like this.
        return 0.0;
    }

    /**
     * Get subscription limits for a user and module.
     */
    public function getAvailableModules()
    {
        return Service::where('type', 'module')->where('is_active', true)->orderBy('sort_order')->get();
    }

    public function getLimits(User $user, string $module): array
    {
        if ($user->hasRole('admin') || $user->hasRole('moderator')) {
            if (!in_array($module, ['freelance'])) {
                return [
                    'projects'     => -1,
                    'invoices'     => -1,
                    'tasks'        => -1,
                    'team_members' => -1,
                ];
            }
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

    public function getAvailablePlans()
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
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
