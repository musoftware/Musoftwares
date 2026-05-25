<?php

namespace Modules\Booking\app\Features\OnlinePage\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PublicBookingViewed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tenantId;
    public $pageId;
    public $ip;

    public function __construct(int $tenantId, int $pageId, ?string $ip)
    {
        $this->tenantId = $tenantId;
        $this->pageId = $pageId;
        $this->ip = $ip;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->tenantId . '.public_pages');
    }

    public function broadcastAs()
    {
        return 'public_booking.viewed';
    }
}
