<?php

namespace App\Services;

use App\Models\User;
use Modules\ERP\Models\ModulePlan;
use Modules\ERP\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Check if a user has active access to a module.
     */
    public function hasActiveSubscription(User $user, string $module): bool
    {
        // 1. Admins have access to everything
        if ($user->hasRole('admin')) {
            return true;
        }

        // Free bypass removed: All modules now strictly require a valid UserSubscription.
        // We will seed "Free Trial" ModulePlans in the database so users can subscribe for $0 to test the platform.

        // 3. Check for active subscription in user_subscriptions
        $activeSub = UserSubscription::where('client_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->whereHas('plan', function ($query) use ($module) {
                $query->where('module', $module)
                      ->where('is_active', true);
            })
            ->first();

        return $activeSub !== null;
    }

    /**
     * Get the active subscription for a user and module.
     */
    public function getActiveSubscription(User $user, string $module): ?UserSubscription
    {
        return UserSubscription::with('plan')
            ->where('client_id', $user->id)
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', Carbon::now());
            })
            ->whereHas('plan', function ($query) use ($module) {
                $query->where('module', $module)
                      ->where('is_active', true);
            })
            ->first();
    }

    /**
     * Get subscription limits for a user and module.
     */
    public function getLimits(User $user, string $module): array
    {
        if ($user->hasRole('admin')) {
            return [
                'projects' => -1, // Unlimited
                'invoices' => -1,
                'tasks' => -1,
                'team_members' => -1,
            ];
        }

        $activeSub = $this->getActiveSubscription($user, $module);

        if (!$activeSub) {
            // Freelancer free tier defaults
            if ($module === 'freelance') {
                return [
                    'connects' => 20,
                    'commission_rate' => 10.0,
                ];
            }
            if ($module === 'erp') {
                return [
                    'projects' => -1, // Unlimited
                    'invoices' => -1, // Unlimited
                    'tasks' => -1, // Unlimited
                    'team_members' => -1, // Unlimited
                ];
            }
            return [
                'projects' => 0,
                'invoices' => 0,
                'tasks' => 0,
                'team_members' => 0,
            ];
        }

        $planName = strtolower($activeSub->plan->name);

        if ($module === 'erp') {
            if (str_contains($planName, 'professional') || str_contains($planName, 'pro')) {
                return [
                    'projects' => -1, // Unlimited
                    'invoices' => -1, // Unlimited
                    'tasks' => -1, // Unlimited
                    'team_members' => 10,
                ];
            }

            // Starter defaults
            return [
                'projects' => 5,
                'invoices' => 10,
                'tasks' => 50,
                'team_members' => 2,
            ];
        }

        if ($module === 'freelance') {
            if (str_contains($planName, 'premium')) {
                return [
                    'connects' => 120, // 20 standard + 100 premium
                    'commission_rate' => 5.0, // Reduced fee
                ];
            }
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
