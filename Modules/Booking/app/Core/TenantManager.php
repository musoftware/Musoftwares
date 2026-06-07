<?php

namespace Modules\Booking\Core;

class TenantManager
{
    protected ?int $tenantId = null;

    /**
     * Set the current active tenant ID for the booking engine.
     *
     * @param int $tenantId
     * @return void
     */
    public function setCurrentTenantId(int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Get the current active tenant ID.
     *
     * @return int|null
     */
    public function getCurrentTenantId(): ?int
    {
        // Fallback to checking the authenticated user's tenant if not explicitly set
        if (! $this->tenantId && auth()->check()) {
            return (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()) ?? null;
        }

        return $this->tenantId;
    }
}
