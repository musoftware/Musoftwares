<?php

namespace Modules\Booking\app\Features\Widget\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WidgetBookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tenantId;
    public $widgetId;
    public $bookingId;

    public function __construct(int $tenantId, int $widgetId, int $bookingId)
    {
        $this->tenantId = $tenantId;
        $this->widgetId = $widgetId;
        $this->bookingId = $bookingId;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->tenantId . '.widgets');
    }

    public function broadcastAs()
    {
        return 'widget.booking.created';
    }
}
