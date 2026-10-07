<?php

namespace App\Services;

use App\Events\LoyaltyPointsAwarded;
use App\Events\LoyaltyTierDowngraded;
use App\Events\LoyaltyTierUpgraded;
use App\Exceptions\MissingExchangeRateException;
use App\Jobs\SendLoyaltyProgressEmailJob;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        $idempotencyKey = $context['idempotency_key'] ?? $this->buildIdempotencyKey($user, $eventType, $reference);

        $points = $this->calculatePoints($rule, $context);

        if ($points <= 0) {
            return null;
        }

        $transaction = $this->runLedgerWriteOnce(function () use ($user, $rule, $eventType, $points, $reference, $context, $idempotencyKey) {
            $locked = User::lockForUpdate()->findOrFail($user->id);

            // Checked under the user lock; the unique idempotency_key index is the final guard.
            if (LoyaltyPointTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                return null;
            }

            $balanceAfter = ((int) $locked->loyalty_points_balance) + $points;

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

            $locked->increment('loyalty_points_balance', $points);
            $locked->increment('loyalty_lifetime_points', $points);

            return $txn;
        }, ['user_id' => $user->id, 'idempotency_key' => $idempotencyKey]);

        if ($transaction === null) {
            return null;
        }

        $this->dispatchAfterCommit(new LoyaltyPointsAwarded($user, $transaction));

        // Reload user to get fresh lifetime points for tier evaluation
        $user->refresh();
        $this->syncLoyaltyTier($user);

        return $transaction;
    }

    /**
     * Run a ledger write in a transaction. A unique-key violation means the entry was already
     * written by a concurrent or repeated call, so it is treated as "already done" (returns null).
     */
    private function runLedgerWriteOnce(callable $work, array $logContext = []): mixed
    {
        try {
            return DB::transaction($work);
        } catch (UniqueConstraintViolationException $e) {
            Log::info('Loyalty ledger entry already recorded, duplicate skipped.', $logContext + ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Fire an event only after the surrounding transaction commits (immediately when there is none),
     * so listeners and mails never act on rolled-back points or tiers.
     */
    private function dispatchAfterCommit(object $event): void
    {
        DB::afterCommit(fn () => event($event));
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
     * Check if a user's total paid invoices in the current calendar month (Africa/Cairo)
     * crossed the monthly spend milestones (10,000 EGP and 15,000 EGP), and award bonus points.
     *
     * @return array<LoyaltyPointTransaction>
     */
    public function checkMonthlySpendMilestones(User $user, ?Invoice $currentInvoice = null, ?Carbon $asOfDate = null): array
    {
        $invoiceDate = $currentInvoice?->paid_at ? Carbon::parse($currentInvoice->paid_at) : null;
        $cairoDate = ($asOfDate ?? $invoiceDate ?? Carbon::now('Africa/Cairo'))->copy()->setTimezone('Africa/Cairo');
        $startOfMonth = $cairoDate->copy()->startOfMonth();
        $endOfMonth = $cairoDate->copy()->endOfMonth();
        $monthKey = $cairoDate->format('Y-m');

        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency?->id;

        // Fetch all paid invoices for the user within this Cairo month
        $invoices = Invoice::where('user_id', $user->id)
            ->where('status', 'paid')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                    ->orWhere(function ($sq) use ($startOfMonth, $endOfMonth) {
                        $sq->whereNull('paid_at')
                            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth]);
                    });
            })
            ->get();

        $totalPaidEgp = 0.0;
        foreach ($invoices as $inv) {
            $amount = (float) ($inv->paid ?? $inv->total());
            if ($amount <= 0) {
                continue;
            }

            $invCurrencyId = $inv->currency_id ?? $inv->currency;
            if ($egpCurrencyId && $invCurrencyId && (int) $invCurrencyId !== (int) $egpCurrencyId) {
                $converted = (float) CurrenciesExchange::RateToday($amount, $invCurrencyId, $egpCurrencyId);
                $totalPaidEgp += $converted;
            } else {
                $totalPaidEgp += $amount;
            }
        }

        $awarded = [];

        // Milestone 1: 10,000 EGP -> 600 points
        if ($totalPaidEgp >= 10000.0) {
            $idempotencyKey10k = "monthly_spend_10k:{$user->id}:{$monthKey}";
            $txn10k = $this->awardPointsForEvent(
                user: $user,
                eventType: 'monthly_spend_10k',
                reference: $currentInvoice,
                context: [
                    'idempotency_key' => $idempotencyKey10k,
                    'month'           => $monthKey,
                    'threshold_egp'   => 10000,
                    'total_paid_egp'  => round($totalPaidEgp, 2),
                    'channel'         => 'monthly_milestone',
                    'invoice_id'      => $currentInvoice?->id,
                ]
            );

            if ($txn10k !== null) {
                $awarded[] = $txn10k;
            }
        }

        // Milestone 2: 15,000 EGP (an additional 5,000 EGP) -> 1,000 points
        if ($totalPaidEgp >= 15000.0) {
            $idempotencyKey15k = "monthly_spend_15k:{$user->id}:{$monthKey}";
            $txn15k = $this->awardPointsForEvent(
                user: $user,
                eventType: 'monthly_spend_15k',
                reference: $currentInvoice,
                context: [
                    'idempotency_key' => $idempotencyKey15k,
                    'month'           => $monthKey,
                    'threshold_egp'   => 15000,
                    'total_paid_egp'  => round($totalPaidEgp, 2),
                    'channel'         => 'monthly_milestone',
                    'invoice_id'      => $currentInvoice?->id,
                ]
            );

            if ($txn15k !== null) {
                $awarded[] = $txn15k;
            }
        }

        return $awarded;
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
    public function syncLoyaltyTier(User $user, ?string $reason = null): void
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
            $user->tier = strtolower($correctTier->slug);
            $user->save();

            if (! $previousTier || $correctTier->min_lifetime_points > $previousTier->min_lifetime_points) {
                $this->dispatchAfterCommit(new LoyaltyTierUpgraded($user, $correctTier, $previousTier));
            } elseif ($correctTier->min_lifetime_points < $previousTier->min_lifetime_points) {
                $this->dispatchAfterCommit(new LoyaltyTierDowngraded($user, $correctTier, $previousTier, $reason));
            }
        } elseif ($user->tier !== strtolower($correctTier->slug)) {
            $user->tier = strtolower($correctTier->slug);
            $user->saveQuietly();
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

        $cost = (int) $reward->points_cost;

        $redemption = DB::transaction(function () use ($user, $reward, $appliedModel, $cost) {
            // Balance is read from the locked row, so two parallel redeems cannot both pass the check.
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $balanceAfter = (int) $locked->loyalty_points_balance - $cost;

            if ($balanceAfter < 0) {
                throw new InvalidArgumentException('Insufficient loyalty points balance.');
            }

            $redemption = LoyaltyRedemption::create([
                'user_id'           => $user->id,
                'loyalty_reward_id' => $reward->id,
                'points_spent'      => $cost,
                'status'            => 'completed',
                'applied_to_type'   => $appliedModel ? get_class($appliedModel) : null,
                'applied_to_id'     => $appliedModel ? $appliedModel->id : null,
                'applied_at'        => now(),
            ]);

            LoyaltyPointTransaction::create([
                'user_id'         => $user->id,
                'event_type'      => 'reward_redemption',
                'points'          => -$cost,
                'balance_after'   => $balanceAfter,
                'source_channel'  => 'web_portal',
                'idempotency_key' => 'redemption:' . $redemption->id,
                'reference_type'  => get_class($reward),
                'reference_id'    => $reward->id,
            ]);

            $locked->loyalty_points_balance = $balanceAfter;
            $locked->save();

            return $redemption;
        });

        $user->refresh();

        return $redemption;
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
        $this->syncLoyaltyTier($user);
        $user->refresh();

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
                $user->tier = strtolower($currentTier->slug);
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
     * $idempotencyKey should identify the admin action (e.g. its legacy PointTransaction id) so a retry
     * cannot apply it twice; a repeated key throws UniqueConstraintViolationException.
     * A deduction larger than the balance is clamped to zero; the ledger records the delta actually applied.
     */
    public function adjustPointsManually(User $user, int $points, string $reason, ?User $admin = null, ?string $idempotencyKey = null): LoyaltyPointTransaction
    {
        if ($points === 0) {
            throw new InvalidArgumentException('Points adjustment cannot be zero.');
        }

        $idempotencyKey ??= 'manual_adj:' . $user->id . ':' . Str::uuid();

        $txn = DB::transaction(function () use ($user, $points, $reason, $admin, $idempotencyKey) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $prevBalance = (int) $locked->loyalty_points_balance;
            $newBalance = max(0, $prevBalance + $points);

            $txn = LoyaltyPointTransaction::create([
                'user_id'          => $user->id,
                'loyalty_rule_id'  => null,
                'event_type'       => $points > 0 ? 'admin_manual_grant' : 'admin_manual_deduction',
                'points'           => $newBalance - $prevBalance,
                'balance_after'    => $newBalance,
                'source_channel'   => 'admin',
                'idempotency_key'  => $idempotencyKey,
                'reference_type'   => $admin ? get_class($admin) : null,
                'reference_id'     => $admin ? $admin->id : null,
                'metadata'         => [
                    'reason'           => $reason,
                    'requested_points' => $points,
                    'admin_name'       => $admin?->name ?? 'Administrator',
                    'admin_id'         => $admin?->id,
                    'adjusted_at'      => now()->toISOString(),
                ],
            ]);

            $locked->loyalty_points_balance = $newBalance;
            if ($points > 0) {
                $locked->loyalty_lifetime_points = (int) $locked->loyalty_lifetime_points + $points;
            }
            $locked->save();

            if ($points > 0) {
                $this->syncLoyaltyTier($locked);
            }

            return $txn;
        });

        $user->refresh();

        return $txn;
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
            case 'points_quarterly_expired':
                return 'Quarterly Points Expiration & Periodic Reset';
            case 'invoice_overdue_penalty':
                $invId = $txn->reference_id ?? $meta['invoice_id'] ?? '';
                $days = $meta['days_overdue'] ?? null;
                $suffix = $days ? " ({$days}d overdue)" : '';
                return $invId ? "Overdue Done Invoice Penalty #{$invId}{$suffix}" : "Overdue Done Invoice Penalty{$suffix}";
            default:
                return ucwords(str_replace('_', ' ', $txn->event_type));
        }
    }

    /**
     * Get quarterly points expiry calculation details in Cairo timezone.
     */
    public function getQuarterlyExpiryInfo(): array
    {
        $now = Carbon::now('Africa/Cairo');
        $quarterEnd = $now->copy()->endOfQuarter()->endOfDay();
        $daysRemaining = max(0, (int) $now->diffInDays($quarterEnd, false));
        $quarterName = 'Q' . $now->quarter . ' ' . $now->year;

        return [
            'quarter_name'           => $quarterName,
            'quarter_number'         => $now->quarter,
            'expiry_date'            => $quarterEnd,
            'expiry_date_formatted'  => $quarterEnd->format('M d, Y'),
            'expiry_date_arabic'     => $quarterEnd->locale('ar')->isoFormat('D MMMM YYYY'),
            'days_remaining'         => $daysRemaining,
            'hours_remaining'        => max(0, (int) $now->diffInHours($quarterEnd, false)),
        ];
    }

    /**
     * Dispatch quarterly points expiration reminders to users with positive spendable balance.
     * Uses tiered notification intervals (14 days, 7 days, 3 days, 1 day) to prevent spam.
     */
    public function sendQuarterlyPointsExpiryReminders(int $daysThreshold = 14, bool $dryRun = false): array
    {
        $expiryInfo = $this->getQuarterlyExpiryInfo();
        $daysRemaining = $expiryInfo['days_remaining'];

        if ($daysRemaining > $daysThreshold) {
            return [
                'sent_count'    => 0,
                'days_remaining'=> $daysRemaining,
                'reason'        => "Current days remaining ({$daysRemaining}) exceeds reminder threshold ({$daysThreshold}).",
            ];
        }

        // Determine bucket for deduplication (14, 7, 3, 1)
        $bucket = 14;
        if ($daysRemaining <= 1) {
            $bucket = 1;
        } elseif ($daysRemaining <= 3) {
            $bucket = 3;
        } elseif ($daysRemaining <= 7) {
            $bucket = 7;
        }

        $quarterName = $expiryInfo['quarter_name'];
        $users = User::where('loyalty_points_balance', '>', 0)
            ->whereNotNull('email')
            ->get();

        $sentCount = 0;
        foreach ($users as $user) {
            $fingerprint = "quarterly_expiry:{$user->id}:{$quarterName}:b{$bucket}";

            if (LoyaltyNotificationLog::alreadySent($fingerprint)) {
                continue;
            }

            if (! $dryRun) {
                \App\Mail\LoyaltyPointsExpiringMail::sendToUser(
                    $user,
                    (int) $user->loyalty_points_balance,
                    $daysRemaining,
                    $expiryInfo['expiry_date']
                );

                LoyaltyNotificationLog::create([
                    'user_id'                   => $user->id,
                    'notification_type'         => 'quarterly_points_expiry',
                    'deduplication_fingerprint' => $fingerprint,
                    'status'                    => 'sent',
                    'sent_at'                   => now(),
                    'metadata'                  => [
                        'points_balance' => (int) $user->loyalty_points_balance,
                        'days_remaining' => $daysRemaining,
                        'quarter_name'   => $quarterName,
                        'bucket'         => $bucket,
                    ],
                ]);
            }

            $sentCount++;
        }

        return [
            'sent_count'    => $sentCount,
            'days_remaining'=> $daysRemaining,
            'quarter_name'  => $quarterName,
            'dry_run'       => $dryRun,
        ];
    }

    /**
     * Perform the actual quarterly reset: clears unspent loyalty points balance to 0,
     * while completely preserving permanent lifetime points and tier status.
     */
    public function expireQuarterlyPoints(?string $quarterLabel = null): int
    {
        $quarterLabel = $quarterLabel ?? ('Q' . Carbon::now('Africa/Cairo')->quarter . ' ' . Carbon::now('Africa/Cairo')->year);

        $userIds = User::where('loyalty_points_balance', '>', 0)->pluck('id');
        $expiredCount = 0;

        foreach ($userIds as $userId) {
            $idempotencyKey = "expiry:{$userId}:{$quarterLabel}";
            $expired = $this->runLedgerWriteOnce(
                fn () => $this->expireUserBalance($userId, $quarterLabel, $idempotencyKey),
                ['user_id' => $userId, 'idempotency_key' => $idempotencyKey]
            );

            $expiredCount += $expired ? 1 : 0;
        }

        return $expiredCount;
    }

    /**
     * Zero one user's spendable balance from the locked row. Returns false when nothing was expired.
     * The key has no timestamp, so re-running the command in the same quarter cannot expire twice.
     */
    private function expireUserBalance(int $userId, string $quarterLabel, string $idempotencyKey): bool
    {
        $user = User::lockForUpdate()->findOrFail($userId);
        $unspent = (int) $user->loyalty_points_balance;

        if ($unspent <= 0 || LoyaltyPointTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
            return false;
        }

        LoyaltyPointTransaction::create([
            'user_id'         => $user->id,
            'event_type'      => 'points_quarterly_expired',
            'points'          => -$unspent,
            'balance_after'   => 0,
            'source_channel'  => 'system',
            'idempotency_key' => $idempotencyKey,
            'metadata'        => [
                'reason'       => "Quarterly points expiration for {$quarterLabel}",
                'points_lost'  => $unspent,
                'quarter_name' => $quarterLabel,
                'expired_at'   => now()->toISOString(),
            ],
        ]);

        $user->loyalty_points_balance = 0;
        $user->saveQuietly();

        return true;
    }

    /**
     * Deduct loyalty points daily for overdue unpaid invoices marked as done (job_status = 'done').
     * Rate: 1 point per 100 EGP per day (or as configured in LoyaltyRule).
     * Deducts from both available balance and lifetime points (can cause tier demotion).
     * Idempotency is enforced per invoice per day in Cairo timezone.
     *
     * @return array{processed_count: int, penalized_invoices: int, total_points_deducted: int, dry_run: bool}
     */
    public function deductOverdueInvoicePenalties(bool $dryRun = false, ?Carbon $asOfDate = null): array
    {
        $cairoNow = ($asOfDate ?? Carbon::now('Africa/Cairo'))->copy()->setTimezone('Africa/Cairo');
        $today = $cairoNow->copy()->startOfDay();
        $todayStr = $today->toDateString();

        $rule = LoyaltyRule::forEvent('invoice_overdue_penalty');
        if ($rule && ! $rule->is_active) {
            return [
                'processed_count'       => 0,
                'penalized_invoices'    => 0,
                'total_points_deducted' => 0,
                'dry_run'               => $dryRun,
                'status'                => 'rule_inactive',
            ];
        }

        $conditions = $rule?->conditions_payload ?? [];
        $egpPerPoint = (float) ($conditions['egp_per_point'] ?? 100.0);
        if ($egpPerPoint <= 0) {
            $egpPerPoint = 100.0;
        }
        $minPoints = (int) ($conditions['min_points_per_day'] ?? 1);
        $affectLifetime = (bool) ($conditions['affect_lifetime_points'] ?? true);

        $egpCurrency = Currency::where('currency', 'EGP')->first();
        $egpCurrencyId = $egpCurrency?->id;

        $invoices = Invoice::with(['user'])
            ->where('job_status', 'done')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->whereNull('deleted_at')
            ->whereNotNull('user_id')
            ->get();

        $processedCount = 0;
        $penalizedInvoices = 0;
        $totalPointsDeducted = 0;

        foreach ($invoices as $invoice) {
            $user = $invoice->user;
            if ($user === null) {
                continue;
            }

            $dueDate = Carbon::parse($invoice->due_date ?? $invoice->created_at, 'Africa/Cairo')->startOfDay();

            if (! $today->greaterThan($dueDate)) {
                continue;
            }

            $daysOverdue = max(1, (int) $dueDate->diffInDays($today));

            $idempotencyKey = "invoice_overdue_penalty:{$invoice->id}:{$todayStr}";
            if (LoyaltyPointTransaction::where('idempotency_key', $idempotencyKey)->exists()) {
                continue;
            }

            $unpaidAmount = (float) ($invoice->unpaid > 0 ? $invoice->unpaid : $invoice->total());
            if ($unpaidAmount <= 0) {
                continue;
            }

            $unpaidEgp = $this->unpaidAmountInEgp($invoice, $unpaidAmount, $egpCurrencyId);
            if ($unpaidEgp === null) {
                continue;
            }

            $penaltyPoints = max($minPoints, (int) round($unpaidEgp / $egpPerPoint));
            $processedCount++;

            if ($dryRun) {
                $penalizedInvoices++;
                $totalPointsDeducted += $penaltyPoints;
                continue;
            }

            DB::transaction(function () use (
                $user,
                $invoice,
                $rule,
                $penaltyPoints,
                $affectLifetime,
                $idempotencyKey,
                $daysOverdue,
                $unpaidAmount,
                $unpaidEgp,
                $egpPerPoint,
                $todayStr
            ) {
                $user = User::lockForUpdate()->findOrFail($user->id);
                $prevBalance = (int) ($user->loyalty_points_balance ?? 0);
                $prevLifetime = (int) ($user->loyalty_lifetime_points ?? 0);

                $newBalance = max(0, $prevBalance - $penaltyPoints);
                $newLifetime = $affectLifetime ? max(0, $prevLifetime - $penaltyPoints) : $prevLifetime;

                LoyaltyPointTransaction::create([
                    'user_id'          => $user->id,
                    'loyalty_rule_id'  => $rule?->id,
                    'event_type'       => 'invoice_overdue_penalty',
                    'points'           => -$penaltyPoints,
                    'balance_after'    => $newBalance,
                    'source_channel'   => 'system',
                    'idempotency_key'  => $idempotencyKey,
                    'reference_type'   => Invoice::class,
                    'reference_id'     => $invoice->id,
                    'metadata'         => [
                        'invoice_id'     => $invoice->id,
                        'days_overdue'   => $daysOverdue,
                        'unpaid_amount'  => $unpaidAmount,
                        'unpaid_egp'     => round($unpaidEgp, 2),
                        'rate'           => "1 pt / {$egpPerPoint} EGP",
                        'penalty_points' => $penaltyPoints,
                        'cairo_date'     => $todayStr,
                        'prev_balance'   => $prevBalance,
                        'prev_lifetime'  => $prevLifetime,
                        'balance_after'  => $newBalance,
                        'lifetime_after' => $newLifetime,
                    ],
                ]);

                $user->loyalty_points_balance = $newBalance;
                $user->loyalty_lifetime_points = $newLifetime;
                $user->save();

                $this->syncLoyaltyTier($user, "Delayed settlement of finished Invoice #{$invoice->id}");
            });

            $penalizedInvoices++;
            $totalPointsDeducted += $penaltyPoints;
        }

        return [
            'processed_count'       => $processedCount,
            'penalized_invoices'    => $penalizedInvoices,
            'total_points_deducted' => $totalPointsDeducted,
            'dry_run'               => $dryRun,
        ];
    }

    /**
     * Unpaid amount in EGP for the overdue penalty. Returns null (and logs) when the invoice's
     * currency has no rate, so one bad currency pair skips that invoice instead of stopping the run.
     */
    private function unpaidAmountInEgp(Invoice $invoice, float $unpaidAmount, ?int $egpCurrencyId): ?float
    {
        $invCurrencyId = $invoice->currency_id ?? $invoice->currency;
        if (! $egpCurrencyId || ! $invCurrencyId || (int) $invCurrencyId === $egpCurrencyId) {
            return $unpaidAmount;
        }

        try {
            return (float) CurrenciesExchange::RateToday($unpaidAmount, $invCurrencyId, $egpCurrencyId);
        } catch (MissingExchangeRateException $e) {
            Log::error('Overdue penalty skipped for invoice: missing exchange rate.', [
                'invoice_id' => $invoice->id,
                'currency_id' => $invCurrencyId,
                'error' => $e->getMessage(),
            ]);

            return null;
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
    private function buildIdempotencyKey(User $user, string $eventType, mixed $reference): string
    {
        // Without a reference the event is once per user (e.g. user_welcome, profile_completed).
        // A bare event name would make it once per whole platform: only the first user ever got it.
        if ($reference === null) {
            return $eventType . ':User:' . $user->id;
        }

        return $eventType . ':' . class_basename($reference) . ':' . $reference->id;
    }
}
