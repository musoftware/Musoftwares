<?php

namespace App\Listeners;

use App\Events\LoyaltyTierUpgraded;
use App\Models\LoyaltyNotificationLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class LoyaltyTierUpgradedMailListener implements ShouldQueue
{
    public string $queue = 'default';

    public function handle(LoyaltyTierUpgraded $event): void
    {
        $user = $event->user;
        $newTier = $event->newTier;

        if (empty($user->email)) {
            return;
        }

        $fingerprint = "tier_upgraded:{$user->id}:{$newTier->id}";

        if (LoyaltyNotificationLog::alreadySent($fingerprint)) {
            return;
        }

        Mail::to($user->email)->queue(
            new \App\Mail\LoyaltyTierUpgradedMail($user, $newTier, $event->previousTier)
        );

        LoyaltyNotificationLog::create([
            'user_id'                   => $user->id,
            'notification_type'         => 'tier_upgraded',
            'deduplication_fingerprint' => $fingerprint,
            'status'                    => 'sent',
            'sent_at'                   => now(),
            'metadata'                  => [
                'new_tier_id'      => $newTier->id,
                'previous_tier_id' => $event->previousTier?->id,
            ],
        ]);
    }
}
