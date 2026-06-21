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
        // Removed Admin bypass: Admins must subscribe to modules to access them.

        // Check specific module subscription in user_subscriptions
        if ($user->hasModuleSubscription($module)) {
            return true;
        }

        // Legacy fallback: old plan_id system gave access to everything
        if ($user->plan_id && $user->subscription_date) {
            return \Carbon\Carbon::parse($user->subscription_date)->isFuture();
        }

        return false;
    }

    /**
     * Check if a user has active access to a specific tool.
     */
    public function hasAccessToTool(User $user, string $toolSlug): bool
    {
        // 1. Check if tool is free
        $tool = collect(config('tools'))->firstWhere('slug', $toolSlug);
        if ($tool && ($tool['is_free'] ?? false)) {
            return true;
        }

        // 2. Points-based tools are never free
        if (in_array($toolSlug, ['freelance', 'facebook-publisher'])) {
            return false;
        }

        // 3. Check if user specifically bought this tool (new module system)
        if ($user->hasModuleSubscription('tool-' . $toolSlug)) {
            return true;
        }

        if ($tool && $user->hasModuleSubscription('tool-' . $tool['guid'])) {
            return true;
        }

        if ($tool && $user->hasModuleSubscription('TOOL-' . strtoupper($tool['guid']))) {
            return true;
        }

        // 4. Check if user has the global 'tools' module subscription (Platform Pass)
        if ($user->hasModuleSubscription('tools')) {
            return true;
        }

        // 5. Fallback: old ToolSubscription model
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
        // Removed Admin bypass

        return $user->hasSubscription();
    }

    public function createSubscription(User $user, Plan $plan, string $cycle, array $customItems = []): UserSubscription
    {
        $amount = $plan->priceFor($cycle); // Assuming Plan has this method or we adjust it

        return UserSubscription::create([]);
    }

    /**
     * Calculate the proration value for an existing subscription.
     * This calculates the remaining unused value of the current subscription.
     */
    public function calculateUpgradeProration(UserSubscription $current, float $newPrice, string $cycle): float
    {
        $now = Carbon::now();
        if (!$current->expires_at || Carbon::parse($current->expires_at)->isPast()) {
            return 0.0;
        }

        $started = $current->started_at ? Carbon::parse($current->started_at) : $now;
        $expires = Carbon::parse($current->expires_at);
        $totalDays = $started->diffInDays($expires);
        
        if ($totalDays <= 0) {
            return 0.0;
        }

        $daysRemaining = $now->diffInDays($expires);
        if ($daysRemaining <= 0) {
            return 0.0;
        }

        // We assume the old price is roughly proportional to the new price, 
        // or we can just fetch the old item price if we had a PricingService instance here.
        // For now, we return the prorated percentage of the remaining days.
        $prorationPercentage = $daysRemaining / $totalDays;
        
        // This is a simplified proration assuming the user pays the $newPrice and gets credit for unused days.
        // To be completely accurate we'd need the original transaction amount, but this approximates the unused portion.
        return round($newPrice * $prorationPercentage, 2);
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
