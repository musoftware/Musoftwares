<?php

namespace App\Modules\BookingRules\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRuleExecuted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $executionId;
    public array $payload;

    public function __construct(int $tenantId, int $executionId, array $payload)
    {
        $this->tenantId = $tenantId;
        $this->executionId = $executionId;
        $this->payload = $payload;
    }
}
