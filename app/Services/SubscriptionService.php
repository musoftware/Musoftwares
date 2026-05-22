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
     * Get the user's active platform subscription (if any).
     */
    public function getActiveSubscription(User $user, ?string $module = null): ?PlatformSubscription
    {
        $query = PlatformSubscription::with('plan')
            ->forUser($user->id)
            ->active();

        // If a module is specified, filter to subscriptions that include it
        if ($module) {
            $query->where(function ($q) use ($module) {
                // Fixed plans: check plan's included_modules
                $q->whereHas('plan', function ($planQ) use ($module) {
                    $planQ->where('is_custom', false)
                        ->where(function ($inner) use ($module) {
                            $inner->whereJsonContains('included_modules', $module)
                                  ->orWhereJsonContains('included_modules', '*');
                        });
                })
                // Custom plans: check custom_items
                ->orWhere(function ($customQ) use ($module) {
                    $customQ->whereHas('plan', function ($planQ) {
                        $planQ->where('is_custom', true);
                    })->whereJsonContains('custom_items', $module);
                });
            });
        }

        return $query->first();
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

        return $this->getActiveSubscription($user, $module) !== null;
    }

    /**
     * Check if a user has active access to a specific tool.
     */
    public function hasAccessToTool(User $user, string $toolSlug): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        $sub = PlatformSubscription::with('plan')
            ->forUser($user->id)
            ->active()
            ->where(function ($q) use ($toolSlug) {
                $q->whereHas('plan', function ($planQ) use ($toolSlug) {
                    $planQ->where('is_custom', false)
                        ->where(function ($inner) use ($toolSlug) {
                            $inner->whereJsonContains('included_tools', $toolSlug)
                                  ->orWhereJsonContains('included_tools', '*');
                        });
                })
                ->orWhere(function ($customQ) use ($toolSlug) {
                    $customQ->whereHas('plan', function ($planQ) {
                        $planQ->where('is_custom', true);
                    })->whereJsonContains('custom_items', $toolSlug);
                });
            })
            ->first();

        return $sub !== null;
    }

    /**
     * Check if user has ANY active platform subscription.
     */
    public function hasAnySubscription(User $user): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return PlatformSubscription::forUser($user->id)->active()->exists();
    }

    /**
     * Calculate the price for a custom plan given selected item slugs.
     */
    public function calculateCustomPrice(array $itemSlugs, string $cycle = 'monthly'): array
    {
        $items = PlatformServiceItem::active()
            ->whereIn('slug', $itemSlugs)
            ->orderBy('sort_order')
            ->get();

        $total = 0;
        $breakdown = [];

        foreach ($items as $item) {
            $price = $item->priceFor($cycle);
            $total += $price;
            $breakdown[] = [
                'slug'  => $item->slug,
                'name'  => $item->name,
                'type'  => $item->type,
                'price' => $price,
            ];
        }

        return [
            'total'     => round($total, 2),
            'cycle'     => $cycle,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get subscription limits for a user and module.
     * Preserves the original behavior for backward compatibility.
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

        $activeSub = $this->getActiveSubscription($user, $module);

        if (!$activeSub) {
            if ($module === 'freelance') {
                return ['connects' => 20, 'commission_rate' => 10.0];
            }
            if ($module === 'erp') {
                return ['projects' => -1, 'invoices' => -1, 'tasks' => -1, 'team_members' => -1];
            }
            return ['projects' => 0, 'invoices' => 0, 'tasks' => 0, 'team_members' => 0];
        }

        $planSlug = $activeSub->plan->slug ?? '';

        if ($module === 'erp') {
            if (in_array($planSlug, ['business_suite', 'professional'])) {
                return ['projects' => -1, 'invoices' => -1, 'tasks' => -1, 'team_members' => 10];
            }
            return ['projects' => 5, 'invoices' => 10, 'tasks' => 50, 'team_members' => 2];
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
