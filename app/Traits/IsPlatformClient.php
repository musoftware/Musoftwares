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
        return $this->hasMany(\Modules\Core\Models\Invoice::class, 'client_id')
                    ->where('tenant_id', Tenant::platformId());
    }

    public function platformWallet()
    {
        return $this->hasOne(\Modules\ERP\Models\ClientWallet::class, 'client_id')
                    ->where('tenant_id', Tenant::platformId());
    }

    public function supportTickets()
    {
        return $this->hasMany(\Modules\ERP\Models\SupportTicket::class, 'client_id')
                    ->where('tenant_id', Tenant::platformId());
    }

    public function projects()
    {
        return $this->hasMany(\Modules\Core\Models\Project::class, 'client_id')
                    ->where('tenant_id', Tenant::platformId());
    }
}
