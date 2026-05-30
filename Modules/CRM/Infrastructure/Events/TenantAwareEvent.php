<?php

namespace Modules\CRM\Infrastructure\Events;

trait TenantAwareEvent
{
    public ?int $tenantId = null;
    public ?int $branchId = null;

    /**
     * Set the tenant context for this event.
     */
    public function setTenantContext(?int $tenantId, ?int $branchId = null): void
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
    }

    /**
     * Get the tenant ID associated with this event.
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Get the branch ID associated with this event.
     */
    public function getBranchId(): ?int
    {
        return $this->branchId;
    }
}
