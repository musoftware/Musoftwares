<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimerUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $itemId;
    public $timeElapsed;
    public $state;

    /**
     * Create a new event instance.
     */
    public function __construct($itemId, $timeElapsed, $state = 'running')
    {
        $this->itemId = $itemId;
        $this->timeElapsed = $timeElapsed;
        $this->state = $state;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('timer.' . $this->itemId),
        ];
    }
}
