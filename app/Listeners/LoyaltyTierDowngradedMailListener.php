<?php

namespace App\Listeners;

use App\Events\LoyaltyTierDowngraded;
use App\Models\LoyaltyNotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class LoyaltyTierDowngradedMailListener implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(LoyaltyTierDowngraded $event): void
    {
        $user = $event->user;
        $newTier = $event->newTier;

        if (empty($user->email)) {
            return;
        }

        $todayStr = now()->toDateString();
        $fingerprint = "tier_downgraded:{$user->id}:{$newTier->id}:{$todayStr}";

        if (LoyaltyNotificationLog::alreadySent($fingerprint)) {
            return;
        }

        Mail::to($user->email)->queue(
            new \App\Mail\LoyaltyTierDowngradedMail($user, $newTier, $event->previousTier, $event->reason)
        );

        LoyaltyNotificationLog::create([
            'user_id'                   => $user->id,
            'notification_type'         => 'tier_downgraded',
            'deduplication_fingerprint' => $fingerprint,
            'status'                    => 'sent',
            'sent_at'                   => now(),
            'metadata'                  => [
                'new_tier_id'      => $newTier->id,
                'previous_tier_id' => $event->previousTier?->id,
                'reason'           => $event->reason,
            ],
        ]);
    }
}
