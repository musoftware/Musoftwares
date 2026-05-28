<?php

namespace Modules\ERP\app\Features\MultiBranch\Managers;

use Illuminate\Support\Facades\Broadcast;

class BranchRealtimeManager
{
    public function broadcastToBranch(int $tenantId, int $branchId, string $event, array $payload): void
    {
        $channel = "private-tenant.{$tenantId}.branch.{$branchId}";
        Broadcast::event($event)->on($channel)->with($payload);
    }

    public function broadcastToGlobal(int $tenantId, string $event, array $payload): void
    {
        $channel = "private-tenant.{$tenantId}.global";
        Broadcast::event($event)->on($channel)->with($payload);
    }
}
