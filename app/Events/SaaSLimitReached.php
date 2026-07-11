<?php

namespace App\Events;

use App\Models\TenantUsage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaaSLimitReached
{
    use Dispatchable, SerializesModels;

    public $tenantUsage;

    public function __construct(TenantUsage $tenantUsage)
    {
        $this->tenantUsage = $tenantUsage;
    }
}
