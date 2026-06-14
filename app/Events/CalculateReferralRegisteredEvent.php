<?php

namespace App\Events;

use App\Models\EarnPerRegister;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalculateReferralRegisteredEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    /** @phpstan-ignore-next-line */
    public function __construct($user, $referral, $ip)
    {
        if (isset($referral) && $referral !== '') {
            $get_ref = \App\Helper\ReferralHelper::GetRef($referral);
            if ($get_ref !== null) {
                $user->ref_user_id = $get_ref->user_id;
                $user->save();
                $get_ref->increment('registered');
            }
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
