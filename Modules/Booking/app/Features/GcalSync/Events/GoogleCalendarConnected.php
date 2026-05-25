<?php

namespace Modules\Booking\app\Features\GcalSync\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GcalSync\Models\GoogleAccount;

class GoogleCalendarConnected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $account;

    public function __construct(GoogleAccount $account)
    {
        $this->account = $account;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->account->tenant_id . '.integrations');
    }

    public function broadcastAs()
    {
        return 'gcal.connected';
    }
}
