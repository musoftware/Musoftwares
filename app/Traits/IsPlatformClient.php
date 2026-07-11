<?php

namespace App\Traits;

use App\Models\Ticket;

/**
 * Allows the User model to act as a Client natively for Platform-level operations.
 *
 * NOTE: ERP invoices and ERP projects are FULLY ISOLATED within the ERP module.
 * This trait no longer references any ERP module models.
 * ERP clients are managed via Modules\ERP\Models\TenantClient, which links back
 * to the platform User via the `user_id` FK on the `erp_tenant_clients` table.
 */
trait IsPlatformClient
{
    /**
     * Platform-level support tickets filed by this user.
     * These are the main system tickets, NOT ERP module tickets.
     * ERP tickets live in erp_support_tickets, managed by Modules\ERP\Models\SupportTicket.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }
}
