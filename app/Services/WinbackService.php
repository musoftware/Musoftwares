<?php

namespace App\Services;

use App\Jobs\ProcessWinbackStageJob;
use App\Models\User;
use App\Models\WinbackEngagement;
use App\Models\WinbackStage;
use Illuminate\Support\Facades\URL;

class WinbackService extends BaseService
{
    /**
     * Update a user's last_activity_at timestamp and immediately cancel
     * any open win-back engagement records (circuit breaker).
     */
    public function touchActivity(User $user, string $activityType): void
    {
        $user->last_activity_at   = now();
        $user->last_activity_type = $activityType;
        $user->save();

        WinbackEngagement::markRecovered($user->id, $activityType);
    }

    /**
     * Generate a signed unsubscribe URL for a user (valid 30 days).
     */
    public function unsubscribeUrl(User $user): string
    {
        return URL::signedRoute('winback.unsubscribe', ['user' => $user->id], now()->addDays(30));
    }

    /**
     * Dispatch win-back stage jobs for all eligible users.
     * Called by the daily cron command.
     */
    public function dispatchPendingStages(): void
    {
        $stages = WinbackStage::activeOrdered();

        foreach ($stages as $stage) {
            $this->dispatchForStage($stage);
        }
    }

    /**
     * Find all users eligible for a specific stage and dispatch a job per user.
     */
    private function dispatchForStage(WinbackStage $stage): void
    {
        $nextStage = WinbackStage::where('is_active', true)
            ->where('days_inactive', '>', $stage->days_inactive)
            ->orderBy('days_inactive')
            ->first();

        $maxDays = $nextStage ? $nextStage->days_inactive : null;

        $query = User::whereNotNull('last_activity_at')
            ->whereNull('winback_unsubscribed_at')
            ->where('last_activity_at', '<=', now()->subDays($stage->days_inactive));

        if ($maxDays !== null) {
            $query->where('last_activity_at', '>', now()->subDays($maxDays));
        }

        $query->whereHas('roles', fn ($q) => $q->where('name', 'client'))
            ->chunk(100, function ($users) use ($stage) {
                foreach ($users as $user) {
                    ProcessWinbackStageJob::dispatch($user->id, $stage->id);
                }
            });
    }

    /**
     * Send the stage email for one specific user if all guards pass.
     * Called inside ProcessWinbackStageJob.
     */
    public function sendStageToUser(User $user, WinbackStage $stage): bool
    {
        if ($this->shouldSuppress($user, $stage)) {
            return false;
        }

        $idempotencyKey = "winback:{$user->id}:{$stage->id}";

        if (WinbackEngagement::alreadySent($user->id, $stage->id)) {
            return false;
        }

        $engagement = WinbackEngagement::create([
            'user_id'          => $user->id,
            'stage_id'         => $stage->id,
            'status'           => 'sent',
            'idempotency_key'  => $idempotencyKey,
            'sent_at'          => now(),
            'metadata'         => [
                'loyalty_balance'       => $user->loyalty_points_balance,
                'loyalty_lifetime_pts'  => $user->loyalty_lifetime_points,
                'loyalty_tier_id'       => $user->loyalty_tier_id,
            ],
        ]);

        \App\Mail\WinbackStageMail::sendToUser($user, $stage, $engagement, $this->unsubscribeUrl($user));

        return true;
    }

    /**
     * Guard checks before sending: unsubscribed, active support ticket, already sent.
     */
    private function shouldSuppress(User $user, WinbackStage $stage): bool
    {
        if ($user->winback_unsubscribed_at !== null) {
            return true;
        }

        // Do not send winback to users with an open support ticket
        $hasOpenTicket = $user->tickets()
            ->whereNotIn('ticket_status', ['closed'])
            ->exists();

        if ($hasOpenTicket) {
            return true;
        }

        return false;
    }
}
