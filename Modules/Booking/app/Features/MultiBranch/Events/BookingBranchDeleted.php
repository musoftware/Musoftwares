<?php

namespace Modules\Booking\app\Features\MultiBranch\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\BookingBranch;

class BookingBranchDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $branch;

    public function __construct(BookingBranch $branch)
    {
        $this->branch = $branch;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->branch->tenant_id . '.branches');
    }

    public function broadcastAs()
    {
        return 'branch.deleted';
    }
}

