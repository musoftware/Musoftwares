<?php

namespace Modules\Booking\app\Features\Recurring\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;

class RecurringSeriesCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $series;

    public function __construct(RecurringSeries $series)
    {
        $this->series = $series;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->series->tenant_id . '.recurring');
    }

    public function broadcastAs()
    {
        return 'recurring.created';
    }
}
