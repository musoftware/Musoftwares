<?php

namespace Modules\Booking\app\Features\GroupSessions\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;

class GroupSessionFull implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $session;

    public function __construct(GroupSession $session)
    {
        $this->session = $session;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->session->tenant_id . '.group_sessions');
    }

    public function broadcastAs()
    {
        return 'group_session.full';
    }
}
