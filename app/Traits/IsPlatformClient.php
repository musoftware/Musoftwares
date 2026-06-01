<?php

namespace App\Traits;

use Modules\ERP\Models\Tenant;

/**
 * Allows the User model to act as a Client natively for Platform-level ERP operations.
 * This prevents the need for duplicate TenantClient records for the main Musoftware platform.
 */
trait IsPlatformClient
{
    public function invoices()
    {
        return $this->hasMany(\Modules\ERP\Models\Invoice::class, 'client_id')
                    ->where(function ($q) {
                        $q->whereNull('tenant_id')
                          ->orWhere('tenant_id', Tenant::platformId());
                    });
    }



    public function tickets()
    {
        return $this->hasMany(\App\Models\Ticket::class, 'user_id')
                    ->where(function ($q) {
                        $q->whereNull('tenant_id')
                          ->orWhere('tenant_id', Tenant::platformId());
                    });
    }

    public function projects()
    {
        return $this->hasMany(\Modules\ERP\Models\Project::class, 'client_id')
                    ->where(function ($q) {
                        $q->whereNull('tenant_id')
                          ->orWhere('tenant_id', Tenant::platformId());
                    });
    }
}
