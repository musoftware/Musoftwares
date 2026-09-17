<?php

namespace App\Services;

use App\Events\LoyaltyPointsAwarded;
use App\Events\LoyaltyTierUpgraded;
use App\Jobs\SendLoyaltyProgressEmailJob;
use App\Models\Invoice;
use App\Models\LoyaltyNotificationLog;
use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LoyaltyService extends BaseService
{
    // How many currency units 1 loyalty point is worth (1/30 ≈ 0.0333 EGP)
    public const POINTS_TO_CURRENCY_RATE = 1 / 30;

    /**
     * Award points to a user for a given event type using the DB-driven rules engine.
     * Idempotency is enforced via the unique idempotency_key constraint.
     *
     * @param  array  $context  Extra data passed to the multiplier calculator (e.g. invoice model)
     */
    public function awardPointsForEvent(
        User $user,
        string $eventType,
        mixed $reference = null,
        array $context = []
    ): ?LoyaltyPointTransaction {
        $rule = LoyaltyRule::forEvent($eventType);

        if ($rule === null) {
            return null;
        }

        $idempotencyKey = $this->buildIdempotencyKey($eventType, $reference);

        // Guard: prevent duplicate awards for the same event instance
        if (LoyaltyPointTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
            return null;
        }

        $points = $this->calculatePoints($rule, $context);

        if ($points <= 0) {
            return null;
        }

        $transaction = DB::transaction(function () use ($user, $rule, $eventType, $points, $reference, $context, $idempotencyKey) {
            $balanceAfter = ((int) $user->loyalty_points_balance) + $points;
            $lifetimeAfter = ((int) $user->loyalty_lifetime_points) + $points;

            $txn = LoyaltyPointTransaction::create([
                'user_id'          => $user->id,
                'loyalty_rule_id'  => $rule->id,
                'event_type'       => $eventType,
                'points'           => $points,
                'balance_after'    => $balanceAfter,
                'source_channel'   => $context['channel'] ?? 'system',
                'idempotency_key'  => $idempotencyKey,
                'reference_type'   => $reference ? get_class($reference) : null,
                'reference_id'     => $reference ? $reference->id : null,
                'metadata'         => $context,
            ]);

            $user->increment('loyalty_points_balance', $points);
            $user->increment('loyalty_lifetime_points', $points);

            return $txn;
        });

        event(new LoyaltyPointsAwarded($user, $transaction));

        // Reload user to get fresh lifetime points for tier evaluation
        $user->refresh();
        $this->syncLoyaltyTier($user);

        return $transaction;
    }

    /**
     * Convenient alias for awardPointsForEvent.
     */
    public function awardPoints(
        User $user,
        string $eventType,
        mixed $reference = null,
        array $context = []
    ): ?LoyaltyPointTransaction {
        return $this->awardPointsForEvent($user, $eventType, $reference, $context);
    }

    /**
     * Compute the final points value for a rule, applying any multipliers from conditions_payload.
     */
    public function calculatePoints(LoyaltyRule $rule, array $context): int
    {
        $base = $rule->base_points;
        $conditions = $rule->conditions_payload ?? [];

        if (empty($conditions['early_payment_multipliers'])) {
            return $base;
        }

        $invoice = $context['invoice'] ?? null;
        if (! $invoice instanceof Invoice) {
            return $base;
        }

        $multiplier = $this->resolveEarlyPaymentMultiplier($invoice, $conditions);

        return (int) round($base * $multiplier);
    }

    /**
     * Resolve the early-payment multiplier based on how many days before due date the invoice was paid.
     */
    public function resolveEarlyPaymentMultiplier(Invoice $invoice, array $conditions): float
    {
        $overdueMultiplier = (float) ($conditions['overdue_multiplier'] ?? 0.0);
        $dueDate = $invoice->due_date ? Carbon::parse($invoice->due_date) : null;

        if ($dueDate === null) {
            return 1.0;
        }

        $paidAt = $invoice->paid_at ?? now();
        $daysEarly = (int) $paidAt->diffInDays($dueDate, false);

        // Overdue payment: paid after due date
        if ($daysEarly < 0) {
            return $overdueMultiplier;
        }

        $steps = collect($conditions['early_payment_multipliers'])
            ->sortByDesc('days_early_min');

        foreach ($steps as $step) {
            if ($daysEarly >= (int) $step['days_early_min']) {
                return (float) $step['multiplier'];
            }
        }

        return 1.0;
    }

    /**
     * Compare user's lifetime points against loyalty_tiers and upgrade if needed.
     * Also checks and dispatches the 75% progress threshold notification.
     */
    public function syncLoyaltyTier(User $user): void
    {
        $balance = (int) ($user->loyalty_points_balance ?? 0);
        $lifetimePoints = (int) ($user->loyalty_lifetime_points ?? 0);

        if ($lifetimePoints < $balance) {
            $lifetimePoints = $balance;
            $user->loyalty_lifetime_points = $balance;
            $user->saveQuietly();
        }

        $correctTier = LoyaltyTier::resolveForPoints($lifetimePoints);

        if ($correctTier === null) {
            return;
        }

        $changed = (int) ($user->loyalty_tier_id) !== (int) $correctTier->id;

        if ($changed) {
            $previousTier = $user->loyalty_tier_id ? LoyaltyTier::find($user->loyalty_tier_id) : null;
            $user->loyalty_tier_id = $correctTier->id;
            $user->save();

            event(new LoyaltyTierUpgraded($user, $correctTier, $previousTier));
        }

        $this->checkProgressThreshold($user, $correctTier, $lifetimePoints);
    }

    /**
     * Fire a progress notification email when the user is between 70% and 85% of the next tier.
     */
    private function checkProgressThreshold(User $user, LoyaltyTier $currentTier, int $lifetimePoints): void
    {
        $nextTier = LoyaltyTier::nextAfter($currentTier);

        if ($nextTier === null) {
            return;
        }

        $pointsNeeded = $nextTier->min_lifetime_points - $currentTier->min_lifetime_points;
        $pointsEarned = $lifetimePoints - $currentTier->min_lifetime_points;
        $progressPct = $pointsNeeded > 0 ? ($pointsEarned / $pointsNeeded) * 100 : 0;

        if ($progressPct < 70 || $progressPct >= 85) {
            return;
        }

        $fingerprint = "tier_progress:{$user->id}:{$nextTier->id}:" . floor($lifetimePoints / 10);

        if (LoyaltyNotificationLog::alreadySent($fingerprint)) {
            return;
        }

        SendLoyaltyProgressEmailJob::dispatch($user->id, $nextTier->id, (int) $progressPct);

        LoyaltyNotificationLog::create([
            'user_id'                    => $user->id,
            'notification_type'          => 'tier_progress',
            'deduplication_fingerprint'  => $fingerprint,
            'status'                     => 'sent',
            'sent_at'                    => now(),
            'metadata'                   => ['progress_pct' => $progressPct, 'next_tier_id' => $nextTier->id],
        ]);
    }

    /**
     * Redeem a loyalty reward, decrementing the spendable balance (not the lifetime total).
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
                'user_id'         => $user->id,
                'event_type'      => 'reward_redemption',
                'points'          => -((int) $reward->points_cost),
                'balance_after'   => (int) $user->loyalty_points_balance,
                'source_channel'  => 'web_portal',
                'idempotency_key' => 'redemption:' . $user->id . ':' . $reward->id . ':' . time(),
                'reference_type'  => get_class($reward),
                'reference_id'    => $reward->id,
            ]);

            return LoyaltyRedemption::create([
                'user_id'           => $user->id,
                'loyalty_reward_id' => $reward->id,
                'points_spent'      => $reward->points_cost,
                'status'            => 'completed',
                'applied_to_type'   => $appliedModel ? get_class($appliedModel) : null,
                'applied_to_id'     => $appliedModel ? $appliedModel->id : null,
                'applied_at'        => now(),
            ]);
        });
    }

    /**
     * Active rewards catalog for the client portal.
     */
    public function getActiveRewards(): Collection
    {
        return LoyaltyReward::where('is_active', true)->orderBy('points_cost')->get();
    }

    /**
     * Summary payload for the client dashboard.
     */
    public function getUserSummary(User $user): array
    {
        $balance = (int) ($user->loyalty_points_balance ?? 0);
        $lifetimePoints = (int) ($user->loyalty_lifetime_points ?? 0);

        // Self-healing / synchronization: Lifetime points can never be lower than the unspent points balance
        if ($lifetimePoints < $balance) {
            $lifetimePoints = $balance;
            $user->loyalty_lifetime_points = $balance;
            $user->saveQuietly();
        }

        $currentTier = $user->loyaltyTier;

        if (! $currentTier) {
            $currentTier = LoyaltyTier::resolveForPoints($lifetimePoints);
            if ($currentTier && ! $user->loyalty_tier_id) {
                $user->loyalty_tier_id = $currentTier->id;
                $user->saveQuietly();
            }
        }

        $nextTier = $currentTier ? LoyaltyTier::nextAfter($currentTier) : null;
        $pointsToNextTier = $nextTier ? max(0, $nextTier->min_lifetime_points - $lifetimePoints) : 0;
        $progressPct = 0;

        if ($nextTier && $currentTier) {
            $range = $nextTier->min_lifetime_points - $currentTier->min_lifetime_points;
            $earned = $lifetimePoints - $currentTier->min_lifetime_points;
            $progressPct = $range > 0 ? min(100, (int) round(($earned / $range) * 100)) : 100;
        } elseif (! $nextTier && $currentTier) {
            // Reached highest tier
            $progressPct = 100;
        }

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
            'balance'               => (int) ($user->loyalty_points_balance ?? 0),
            'lifetime_points'       => $lifetimePoints,
            'current_tier'          => $currentTier,
            'next_tier'             => $nextTier,
            'points_to_next_tier'   => $pointsToNextTier,
            'progress_percentage'   => $progressPct,
            'tier'                  => $currentTier?->slug ?? 'bronze',
            'profile_completion'    => (int) ($user->profile_completion_percentage ?? 25),
            'recent_transactions'   => $transactions,
            'recent_redemptions'    => $redemptions,
            'points_to_currency_rate' => self::POINTS_TO_CURRENCY_RATE,
        ];
    }

    /**
     * Manually adjust a user's points (admin override / courtesy grace points).
     * Creates a fully transparent ledger transaction with the admin's mandatory reason.
     */
    public function adjustPointsManually(User $user, int $points, string $reason, ?User $admin = null): LoyaltyPointTransaction
    {
        if ($points === 0) {
            throw new InvalidArgumentException('Points adjustment cannot be zero.');
        }

        return DB::transaction(function () use ($user, $points, $reason, $admin) {
            $newBalance = max(0, ((int) $user->loyalty_points_balance) + $points);
            $newLifetime = $points > 0 ? ((int) $user->loyalty_lifetime_points) + $points : (int) $user->loyalty_lifetime_points;

            $txn = LoyaltyPointTransaction::create([
                'user_id'          => $user->id,
                'loyalty_rule_id'  => null,
                'event_type'       => $points > 0 ? 'admin_manual_grant' : 'admin_manual_deduction',
                'points'           => $points,
                'balance_after'    => $newBalance,
                'source_channel'   => 'admin',
                'idempotency_key'  => 'manual_adj:' . $user->id . ':' . uniqid() . ':' . time(),
                'reference_type'   => $admin ? get_class($admin) : null,
                'reference_id'     => $admin ? $admin->id : null,
                'metadata'         => [
                    'reason'       => $reason,
                    'admin_name'   => $admin?->name ?? 'Administrator',
                    'admin_id'     => $admin?->id,
                    'adjusted_at'  => now()->toISOString(),
                ],
            ]);

            $user->loyalty_points_balance = $newBalance;
            if ($points > 0) {
                $user->loyalty_lifetime_points = $newLifetime;
            }
            $user->save();

            if ($points > 0) {
                $this->syncLoyaltyTier($user);
            }

            return $txn;
        });
    }

    /**
     * Fetch paginated points ledger with formatted human-readable details for complete transparency.
     */
    public function getPaginatedLedger(User $user, int $perPage = 15)
    {
        return LoyaltyPointTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate($perPage)
            ->through(function ($txn) {
                return [
                    'id'             => $txn->id,
                    'event_type'     => $txn->event_type,
                    'title'          => $this->resolveTransactionTitle($txn),
                    'points'         => (int) $txn->points,
                    'balance_after'  => (int) $txn->balance_after,
                    'channel'        => $txn->source_channel ?? 'system',
                    'reference_type' => $txn->reference_type ? class_basename($txn->reference_type) : null,
                    'reference_id'   => $txn->reference_id,
                    'metadata'       => $txn->metadata,
                    'date'           => $txn->created_at ? $txn->created_at->setTimezone('Africa/Cairo')->format('Y-m-d H:i') : '-',
                    'diff_for_humans'=> $txn->created_at ? $txn->created_at->diffForHumans() : '-',
                ];
            });
    }

    /**
     * Resolve a clear human-readable title for ledger entries.
     */
    public function resolveTransactionTitle(LoyaltyPointTransaction $txn): string
    {
        $meta = $txn->metadata ?? [];

        switch ($txn->event_type) {
            case 'invoice_payment':
                $invId = $txn->reference_id ?? $meta['invoice_id'] ?? '';
                return $invId ? "Invoice Payment #{$invId}" : 'Invoice Payment Settlement';
            case 'ticket_portal_created':
                return 'Support Ticket Created via Client Portal';
            case 'ticket_resolved_positive':
                return 'Support Ticket Resolved with High Satisfaction';
            case 'user_welcome':
                return 'Welcome to Musoftwares Loyalty Community';
            case 'profile_completed':
                return 'Corporate Profile 100% Finalized';
            case 'winback_bonus':
                return 'Re-engagement & Welcome Back Courtesy Bonus';
            case 'referral_registered':
                return 'Referred Colleague Registered Account';
            case 'referral_first_payment':
                return 'Referred Colleague Paid First Invoice';
            case 'reward_redemption':
                return 'Points Redeemed for Service Benefit';
            case 'admin_manual_grant':
                $reason = $meta['reason'] ?? 'Courtesy Grace Points';
                return "Admin Courtesy Bonus: {$reason}";
            case 'admin_manual_deduction':
                $reason = $meta['reason'] ?? 'Administrative Adjustment';
                return "Admin Correction: {$reason}";
            default:
                return ucwords(str_replace('_', ' ', $txn->event_type));
        }
    }

    /**
     * List all loyalty tiers ordered by progression.
     */
    public function getAllTiers(): Collection
    {
        return LoyaltyTier::orderBy('min_lifetime_points', 'asc')->get();
    }

    /**
     * Build a deterministic idempotency key for a given event + reference.
     */
    private function buildIdempotencyKey(string $eventType, mixed $reference): string
    {
        if ($reference === null) {
            return $eventType;
        }

        return $eventType . ':' . class_basename($reference) . ':' . $reference->id;
    }
}
