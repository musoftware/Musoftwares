<?php

namespace Modules\CRM\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadSlaBreachedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $branchId;
    public int $breachCount;
    public int $tenantId;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(int $tenantId, int $branchId, int $breachCount)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->breachCount = $breachCount;
    }
}
