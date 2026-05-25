<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\TenantUsage;

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
