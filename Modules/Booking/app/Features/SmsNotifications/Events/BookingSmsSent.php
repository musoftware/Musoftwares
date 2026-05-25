<?php

namespace Modules\Booking\app\Features\SmsNotifications\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsLog;

class BookingSmsSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;

    public function __construct(SmsLog $log)
    {
        $this->log = $log;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->log->tenant_id . '.sms');
    }

    public function broadcastAs()
    {
        return 'sms.sent';
    }
}
