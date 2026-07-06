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

    public $user;
    public $referral;
    public $ip;

    /**
     * Create a new event instance.
     *
     * The constructor is intentionally side-effect-free. Resolving the ref
     * and incrementing the `registered` counter happen in the listener
     * (CalculateReferralListener) so the event remains cheap to dispatch and
     * can be replayed safely from queues.
     *
     * @return void
     */
    public function __construct($user, $referral, $ip)
    {
        $this->user = $user;
        $this->referral = $referral;
        $this->ip = $ip;
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