<?php

namespace Modules\Booking\app\Features\QueueManagement\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueEntry;

class QueueTokenCalled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $entry;

    public function __construct(BookingQueueEntry $entry)
    {
        $this->entry = $entry;
    }

    public function broadcastOn(): array
    {
        return [
            // Private channel for staff dashboards
            new PrivateChannel("tenant.{$this->entry->tenant_id}.queues.{$this->entry->queue_id}"),
            // Public channel for TVs/Displays (unauthenticated screens in waiting room)
            new Channel("public.queue.{$this->entry->queue_id}.displays"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->entry->id,
            'token_number' => $this->entry->token_number,
            'status' => 'called', // Audio chime will trigger on frontend
            'called_at' => $this->entry->called_at->toIso8601String(),
            'walkin_name' => $this->entry->walkin_name,
        ];
    }
}
