<?php

namespace App\Services;

use App\Models\User;

class TierPriorityService extends BaseService
{
    public const TIER_ENTERPRISE_MIN = 10000.00;
    public const TIER_PRO_MIN = 2500.00;

    public const TIER_WEIGHTS = [
        'enterprise' => 500,
        'pro'        => 250,
        'standard'   => 50,
    ];

    public const URGENCY_WEIGHTS = [
        'critical' => 300,
        'high'     => 150,
        'normal'   => 50,
        'low'      => 20,
    ];

    /**
     * Determine user's tier based on lifetime spend and persist if changed.
     */
    public function syncUserTier(User $user): string
    {
        $spend = (float) ($user->lifetime_spend ?? 0.0);

        $newTier = match (true) {
            $spend >= self::TIER_ENTERPRISE_MIN => 'enterprise',
            $spend >= self::TIER_PRO_MIN        => 'pro',
            default                             => 'standard',
        };

        if ($user->tier !== $newTier) {
            $user->tier = $newTier;
            $user->save();
        }

        return $newTier;
    }

    /**
     * Record new spend for user and evaluate tier promotion.
     */
    public function recordSpend(User $user, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $user->lifetime_spend = (float) ($user->lifetime_spend ?? 0.0) + $amount;
        $user->save();

        $this->syncUserTier($user);
    }

    /**
     * Calculate programmatic priority score for a support ticket.
     * High score tickets automatically sort to the top of support queues.
     */
    public function calculateTicketScore(User $user, string $urgency = 'normal'): int
    {
        $tier = $user->tier ?? 'standard';
        $tierWeight = self::TIER_WEIGHTS[$tier] ?? self::TIER_WEIGHTS['standard'];
        $urgencyWeight = self::URGENCY_WEIGHTS[$urgency] ?? self::URGENCY_WEIGHTS['normal'];
        $spendBonus = min(200, (int) (((float) ($user->lifetime_spend ?? 0.0)) / 100));

        return ($tierWeight * 10) + $urgencyWeight + $spendBonus;
    }
}
