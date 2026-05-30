<?php

namespace Modules\CRM\Infrastructure\Context;

use Illuminate\Support\Facades\Log;

class TenantContext
{
    protected ?int $workspaceId = null;

    /**
     * Get the current active workspace ID.
     */
    public function getWorkspaceId(): ?int
    {
        return $this->workspaceId;
    }

    /**
     * Set the current active workspace ID.
     */
    public function setWorkspaceId(?int $id): void
    {
        $this->workspaceId = $id;
    }

    /**
     * Run a callback within a specific workspace context without leaking it permanently.
     */
    public function runInContext(int $workspaceId, callable $callback)
    {
        $previousId = $this->workspaceId;
        
        try {
            $this->setWorkspaceId($workspaceId);
            return $callback();
        } finally {
            $this->setWorkspaceId($previousId);
        }
    }

    /**
     * Clear the context completely.
     */
    public function clear(): void
    {
        $this->workspaceId = null;
    }
}
