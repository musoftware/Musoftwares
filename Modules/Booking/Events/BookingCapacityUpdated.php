<?php

namespace Modules\Booking\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\BookingEventType;

class BookingCapacityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $eventType;
    public $startsAt;
    public $remainingCapacity;

    public function __construct(BookingEventType $eventType, string $startsAt, int $remainingCapacity)
    {
        $this->eventType = $eventType;
        $this->startsAt = $startsAt;
        $this->remainingCapacity = $remainingCapacity;
    }

    public function broadcastOn(): array
    {
        // Public channel so widgets can listen for capacity changes
        return [
            new Channel('booking.event_type.' . $this->eventType->id),
        ];
    }
    
    public function broadcastAs(): string
    {
        return 'capacity.updated';
    }
}
