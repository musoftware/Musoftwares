<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\TenantUsage;

class SaaSLimitReached
{
    use Dispatchable, SerializesModels;

    public $tenantUsage;

    public function __construct(TenantUsage $tenantUsage)
    {
        $this->tenantUsage = $tenantUsage;
    }
}
