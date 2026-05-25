<?php

namespace App\Modules\BookingRules\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingRuleFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $executionId;
    public string $errorMessage;

    public function __construct(int $tenantId, int $executionId, string $errorMessage)
    {
        $this->tenantId = $tenantId;
        $this->executionId = $executionId;
        $this->errorMessage = $errorMessage;
    }
}
