<?php

namespace App\Events;

use App\Models\TenantUsage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaaSLimitApproaching
{
    use Dispatchable, SerializesModels;

    public $tenantUsage;

    public $percentageUsed;

    public function __construct(TenantUsage $tenantUsage, float $percentageUsed)
    {
        $this->tenantUsage = $tenantUsage;
        $this->percentageUsed = $percentageUsed;
    }
}
