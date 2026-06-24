<?php

namespace App\Listeners;

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
        
        if (isset($event->user) && $event->user->ref_user_id) {
            $referrer = \App\Models\User::find($event->user->ref_user_id);
            if ($referrer) {
                // Here we would look up the specific reward value from settings
                // Defaulting to 0 since legacy EarnPerRegister returned 0
                $rewardAmount = 0; 
                
                if ($rewardAmount > 0) {
                    // Assuming add_balance method exists on User model for wallet features
                    if (method_exists($referrer, 'add_balance')) {
                        $referrer->add_balance($rewardAmount, 'Referral Bonus for new registration', 'received');
                    }
                    Log::info("Referral bonus of {$rewardAmount} added to referrer ID {$referrer->id}");
                }
            }
        }
    }
}
