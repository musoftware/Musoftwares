<?php

namespace Modules\Booking\app\Features\MultiBranch\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\BookingBranch;

class BranchStaffAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $branch;
    public $user;
    public $role;

    public function __construct(BookingBranch $branch, $user, string $role)
    {
        $this->branch = $branch;
        $this->user = $user;
        $this->role = $role;
    }

    /**
     * Broadcast to the tenant's global channel
     */
    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->branch->tenant_id . '.branches');
    }

    public function broadcastAs()
    {
        return 'branch.staff.assigned';
    }
}

