<?php

namespace Modules\Booking\app\Features\TeamMembers\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;

class TeamMemberProfileUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $teamMember;

    public function __construct(BookingTeamMember $teamMember)
    {
        $this->teamMember = $teamMember;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->teamMember->tenant_id . '.team');
    }

    public function broadcastAs()
    {
        return 'team.member.updated';
    }
}
