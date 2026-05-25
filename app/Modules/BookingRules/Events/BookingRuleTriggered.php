<?php

namespace App\Modules\BookingRules\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRuleTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public string $eventTrigger;
    public array $payload;

    public function __construct(int $tenantId, string $eventTrigger, array $payload)
    {
        $this->tenantId = $tenantId;
        $this->eventTrigger = $eventTrigger;
        $this->payload = $payload;
    }
}
