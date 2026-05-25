<?php

namespace Modules\Booking\app\Features\GroupSessions\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GroupSessions\Models\GroupParticipant;

class GroupParticipantRemoved implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $participant;

    public function __construct(GroupParticipant $participant)
    {
        $this->participant = $participant;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->participant->tenant_id . '.group_sessions');
    }

    public function broadcastAs()
    {
        return 'participant.removed';
    }
}
