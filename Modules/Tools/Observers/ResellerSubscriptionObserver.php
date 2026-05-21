<?php

namespace Modules\Tools\Observers;

use Modules\Tools\Models\ToolSubscription;
use Modules\Tools\Models\ToolResellerUser;

class ResellerSubscriptionObserver
{
    /**
     * When a tool subscription is created, check if the subscriber belongs
     * to a reseller and deduct the cost from the reseller's balance.
     */
    public function created(ToolSubscription $subscription): void
    {
        // Skip if free
        if ($subscription->amount_paid <= 0) {
            return;
        }

        // Check if this user belongs to a reseller
        $resellerUser = ToolResellerUser::with('reseller')
            ->where('user_id', $subscription->user_id)
            ->where('status', 'active')
            ->first();

        if (!$resellerUser || !$resellerUser->reseller) {
            return;
        }

        $reseller = $resellerUser->reseller;

        // Only deduct if the reseller is active
        if (!$reseller->isActive()) {
            return;
        }

        $toolConfig = config("tools.{$subscription->tool_guid}");
        $toolTitle  = $toolConfig['title'] ?? $subscription->tool_guid;

        $reseller->deductBalance(
            amount:      $subscription->amount_paid,
            description: "Tool subscription: {$toolTitle} for user #{$subscription->user_id}",
            userId:      $subscription->user_id,
            reference:   $subscription->id,
            type:        'charge',
        );
    }
}
