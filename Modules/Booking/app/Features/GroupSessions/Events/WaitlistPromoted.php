<?php

namespace Modules\Booking\app\Features\GroupSessions\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GroupSessions\Models\GroupWaitlist;

class WaitlistPromoted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $waitlistEntry;

    public function __construct(GroupWaitlist $waitlistEntry)
    {
        $this->waitlistEntry = $waitlistEntry;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->waitlistEntry->tenant_id . '.group_sessions');
    }

    public function broadcastAs()
    {
        return 'waitlist.promoted';
    }
}
