<?php

namespace App\Listeners;

use App\Models\UserReferral;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class CalculateReferralListener
{
    public function __construct()
    {
        //
    }

    public function handle(object $event): void
    {
        Log::info('CalculateReferralListener handled CalculateReferralRegisteredEvent.', [
            'user_id' => $event->user->id ?? null,
            'referral' => $event->referral ?? null,
        ]);

        // Resolve the referral key and increment the `registered` counter
        // here, in the listener, rather than in the event constructor.
        // Constructor side-effects broke replay and queued dispatch.
        if (!empty($event->referral)) {
            $ref = UserReferral::resolveRef((string) $event->referral);
            if ($ref !== null) {
                $ref->increment('registered');
            }
        }

        // No referral sign-up reward is paid in this codebase. The legacy
        // EarnPerRegister::regEquivalent() returned 0, and there is no settings
        // surface to configure a non-zero value. Keeping this listener as an
        // explicit no-op so the queue wiring stays intact for future reward
        // logic without misleading anyone reading the logs.
        //
        // When a real reward is introduced, set $rewardAmount from settings
        // and call $referrer->add_balance($rewardAmount, 'Referral Bonus ...', 'received').
        return;
    }
}