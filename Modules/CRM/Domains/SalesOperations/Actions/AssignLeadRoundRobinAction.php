<?php

namespace Modules\CRM\Domains\SalesOperations\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignLeadRoundRobinAction
{
    /**
     * Assign a lead using a strict round-robin logic with atomic database locks
     * to prevent queue race conditions.
     */
    public function execute(int $leadId, int $branchId): void
    {
        DB::transaction(function () use ($leadId, $branchId) {
            // Get all eligible telesales agents for this branch
            $agents = User::where('branch_id', $branchId)
                ->whereHas('subscriptions', function ($q) {
                    $q->where('module_id', 'crm-sales-staff');
                })
                ->orderBy('last_assigned_lead_at', 'asc') // Simple round-robin approach
                ->lockForUpdate() // Prevent race conditions
                ->get();

            if ($agents->isEmpty()) {
                Log::warning("No eligible telesales agents found in branch {$branchId} for lead {$leadId}");
                return;
            }

            $selectedAgent = $agents->first();

            DB::table('leads')->where('id', $leadId)->update([
                'assigned_to_id' => $selectedAgent->id,
                'reassigned_at' => now(),
            ]);

            // Update the agent's last assigned timestamp
            $selectedAgent->update(['last_assigned_lead_at' => now()]);

            Log::info("Lead {$leadId} successfully assigned to agent {$selectedAgent->id} via Round-Robin.");
        });
    }
}
