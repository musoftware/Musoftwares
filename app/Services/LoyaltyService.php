<?php

namespace App\Services;

use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoyaltyService extends BaseService
{
    public const EVENT_POINTS = [
        'ticket_self_opened' => 15,
        'profile_completed'  => 50,
        'brief_submitted'    => 100,
        'invoice_paid'       => 200,
    ];

    // How many currency units 1 loyalty point is worth (e.g. 1/30 ≈ 0.0333 EGP → 150 PTS = 5 EGP)
    public const POINTS_TO_CURRENCY_RATE = 1 / 30;

    /**
     * Award loyalty points to a user for self-service or platform achievements.
     */
    public function awardPoints(User $user, string $eventType, mixed $reference = null, array $metadata = []): ?LoyaltyPointTransaction
    {
        if (! isset(self::EVENT_POINTS[$eventType])) {
            return null;
        }

        // Prevent duplicate single-time awards
        if ($eventType === 'profile_completed') {
            $alreadyAwarded = LoyaltyPointTransaction::where('user_id', $user->id)
                ->where('event_type', $eventType)
                ->exists();

            if ($alreadyAwarded) {
                return null;
            }
        }

        if ($eventType === 'brief_submitted' && $reference) {
            $alreadyAwarded = LoyaltyPointTransaction::where('user_id', $user->id)
                ->where('event_type', $eventType)
                ->where('reference_type', get_class($reference))
                ->where('reference_id', $reference->id)
                ->exists();

            if ($alreadyAwarded) {
                return null;
            }
        }

        $points = self::EVENT_POINTS[$eventType];

        return DB::transaction(function () use ($user, $eventType, $points, $reference, $metadata) {
            $transaction = LoyaltyPointTransaction::create([
                'user_id' => $user->id,
                'event_type' => $eventType,
                'points' => $points,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'metadata' => $metadata,
            ]);

            $user->increment('loyalty_points_balance', $points);

            return $transaction;
        });
    }

    /**
     * Redeem a reward from the loyalty catalog.
     */
    public function redeemReward(User $user, LoyaltyReward $reward, mixed $appliedModel = null): LoyaltyRedemption
    {
        if (! $reward->is_active) {
            throw new InvalidArgumentException('This reward is currently inactive.');
        }

        if ((int) $user->loyalty_points_balance < (int) $reward->points_cost) {
            throw new InvalidArgumentException('Insufficient loyalty points balance.');
        }

        return DB::transaction(function () use ($user, $reward, $appliedModel) {
            $user->decrement('loyalty_points_balance', $reward->points_cost);

            LoyaltyPointTransaction::create([
                'user_id' => $user->id,
                'event_type' => 'reward_redemption',
                'points' => -((int) $reward->points_cost),
                'reference_type' => get_class($reward),
                'reference_id' => $reward->id,
            ]);

            return LoyaltyRedemption::create([
                'user_id' => $user->id,
                'loyalty_reward_id' => $reward->id,
                'points_spent' => $reward->points_cost,
                'status' => 'completed',
                'applied_to_type' => $appliedModel ? get_class($appliedModel) : null,
                'applied_to_id' => $appliedModel ? $appliedModel->id : null,
                'applied_at' => now(),
            ]);
        });
    }

    /**
     * Get active rewards catalog for the client.
     */
    public function getActiveRewards(): Collection
    {
        return LoyaltyReward::where('is_active', true)->orderBy('points_cost', 'asc')->get();
    }

    /**
     * Get loyalty summary payload for the client dashboard.
     */
    public function getUserSummary(User $user): array
    {
        $transactions = LoyaltyPointTransaction::where('user_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        $redemptions = LoyaltyRedemption::with('reward')
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        return [
            'balance' => (int) ($user->loyalty_points_balance ?? 0),
            'tier' => $user->tier ?? 'standard',
            'profile_completion_percentage' => (int) ($user->profile_completion_percentage ?? 25),
            'recent_transactions' => $transactions,
            'recent_redemptions' => $redemptions,
            'points_to_currency_rate' => self::POINTS_TO_CURRENCY_RATE,
        ];
    }
}
