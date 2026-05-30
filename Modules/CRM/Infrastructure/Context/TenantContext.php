<?php

namespace Modules\CRM\Infrastructure\Context;

use Illuminate\Support\Facades\Log;

class TenantContext
{
    protected ?int $tenantId = null;
    protected ?int $branchId = null;

    /**
     * Get the current active tenant (workspace) ID.
     */
    public function getTenantId(): ?int
    {
        return $this->tenantId;
    }

    /**
     * Legacy alias for getTenantId
     */
    public function getWorkspaceId(): ?int
    {
        return $this->getTenantId();
    }

    /**
     * Set the current active tenant (workspace) ID.
     */
    public function setTenantId(?int $id): void
    {
        $this->tenantId = $id;
    }

    /**
     * Legacy alias for setTenantId
     */
    public function setWorkspaceId(?int $id): void
    {
        $this->setTenantId($id);
    }

    /**
     * Get the current active branch ID.
     */
    public function getBranchId(): ?int
    {
        return $this->branchId;
    }

    /**
     * Set the current active branch ID.
     */
    public function setBranchId(?int $id): void
    {
        $this->branchId = $id;
    }

    /**
     * Run a callback within a specific workspace context without leaking it permanently.
     */
    public function runInContext(int $tenantId, callable $callback, ?int $branchId = null)
    {
        $previousTenantId = $this->tenantId;
        $previousBranchId = $this->branchId;
        
        try {
            $this->setTenantId($tenantId);
            if ($branchId !== null) {
                $this->setBranchId($branchId);
            }
            return $callback();
        } finally {
            $this->setTenantId($previousTenantId);
            $this->setBranchId($previousBranchId);
        }
    }

    /**
     * Clear the context completely.
     */
    public function clear(): void
    {
        $this->tenantId = null;
        $this->branchId = null;
    }
}
