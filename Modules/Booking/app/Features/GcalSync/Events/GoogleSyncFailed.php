<?php

namespace Modules\Booking\app\Features\GcalSync\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;

class GoogleSyncFailed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $calendar;
    public $reason;

    public function __construct(GoogleCalendar $calendar, string $reason)
    {
        $this->calendar = $calendar;
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->calendar->tenant_id . '.integrations');
    }

    public function broadcastAs()
    {
        return 'gcal.failed';
    }
}
