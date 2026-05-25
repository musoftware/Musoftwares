<?php

namespace Modules\Booking\app\Features\QueueManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueEntry;

class QueueEntryCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $entry;

    public function __construct(BookingQueueEntry $entry)
    {
        $this->entry = $entry;
    }

    public function broadcastOn(): array
    {
        // Broadcasts to the private tenant queue dashboard so it's removed from active lists
        return [
            new PrivateChannel("tenant.{$this->entry->tenant_id}.queues.{$this->entry->queue_id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->entry->id,
            'token_number' => $this->entry->token_number,
            'status' => $this->entry->status,
            'completed_at' => $this->entry->completed_at->toIso8601String(),
        ];
    }
}
