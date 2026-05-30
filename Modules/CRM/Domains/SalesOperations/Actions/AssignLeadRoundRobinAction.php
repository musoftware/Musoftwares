<?php

namespace Modules\CRM\Domains\SalesOperations\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class AssignLeadRoundRobinAction
{
    /**
     * Assign a lead using Redis atomic counters to prevent MySQL lock contention
     * under high webhook concurrency.
     */
    public function execute(int $leadId, int $branchId): void
    {
        // 1. Get eligible agents for this branch. We cache this briefly in real production
        // to avoid hitting MySQL on every webhook, but we pull it here for clarity.
        $agents = User::where('branch_id', $branchId)
            ->whereHas('subscriptions', function ($q) {
                $q->where('module_id', 'crm-sales-staff');
            })
            ->orderBy('id', 'asc') // Deterministic order
            ->pluck('id')
            ->toArray();

        if (empty($agents)) {
            Log::warning("No eligible telesales agents found in branch {$branchId} for lead {$leadId}");
            return;
        }

        // 2. Atomically increment the pointer in Redis
        $redisKey = "crm:round_robin:branch:{$branchId}";
        $pointer = Redis::incr($redisKey);

        // 3. Determine the selected agent using modulo
        $agentCount = count($agents);
        $selectedIndex = ($pointer - 1) % $agentCount;
        $selectedAgentId = $agents[$selectedIndex];

        // 4. Assign the lead (Simple UPDATE, no lock contention on users table)
        DB::table('leads')->where('id', $leadId)->update([
            'assigned_to_id' => $selectedAgentId,
            'reassigned_at' => now(),
        ]);

        // Note: We no longer need to update `last_assigned_lead_at` on the user model,
        // because the Redis pointer is the single source of truth for the queue!

        Log::info("Lead {$leadId} successfully assigned to agent {$selectedAgentId} via Redis Round-Robin.");
    }
}
