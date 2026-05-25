<?php

namespace Modules\Booking\app\Features\Recurring\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Carbon\Carbon;

class OccurrenceSkipped implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $series;
    public $date;
    public $reason;

    public function __construct(RecurringSeries $series, Carbon $date, string $reason)
    {
        $this->series = $series;
        $this->date = $date->format('Y-m-d');
        $this->reason = $reason;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->series->tenant_id . '.recurring');
    }

    public function broadcastAs()
    {
        return 'occurrence.skipped';
    }
}
