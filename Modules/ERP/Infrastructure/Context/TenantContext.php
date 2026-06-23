<?php

namespace Modules\ERP\Infrastructure\Context;

class TenantContext
{
    protected ?int $tenantId = null;

    /**
     * Get the current active tenant ID.
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Set the current active tenant ID.
     */
    public function setTenantId(?int $id): void
    {
        $this->tenantId = $id;
    }

    /**
     * Run a callback within a specific tenant context without leaking it permanently.
     */
    public function runInContext(int $tenantId, callable $callback)
    {
        $previousTenantId = $this->tenantId;
        
        try {
            $this->setTenantId($tenantId);
            return $callback();
        } finally {
            $this->setTenantId($previousTenantId);
        }
    }

    /**
     * Clear the context completely.
     */
    public function clear(): void
    {
        $this->tenantId = null;
    }
}
