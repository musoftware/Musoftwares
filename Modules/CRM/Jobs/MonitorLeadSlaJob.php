<?php

namespace Modules\CRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Infrastructure\Queue\RequiresAddonMiddleware;

class MonitorLeadSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [new RequiresAddonMiddleware('crm-advanced-operations')];
    }

    public function handle(): void
    {
        Log::info("Starting MonitorLeadSlaJob for tenant {$this->tenantId}...");

        // Process in chunks to prevent memory explosion
        DB::table('leads')
            ->where('tenant_id', $this->tenantId)
            ->whereNotNull('sla_breach_at')
            ->where('sla_breach_at', '<', now())
            ->where('is_stale', false)
            ->orderBy('id')
            ->chunk(500, function ($breachedLeads) {

                $leadIds = $breachedLeads->pluck('id')->toArray();
                
                // Group by branch for digest notification (mocked dispatch here)
                $branchGroups = $breachedLeads->groupBy('branch_id');
                foreach ($branchGroups as $branchId => $leads) {
                    Log::info("SLA Digest: Branch {$branchId} has {$leads->count()} new SLA breaches.");
                    // dispatch(new SendSlaDigestNotification($branchId, $leads->count()));
                }

                // Batch update to mark as stale
                DB::table('leads')->whereIn('id', $leadId)->update([
                    'is_stale' => true,
                    'pipeline_stage' => 'NEW',
                ]);

                // Batch insert activities
                $activities = [];
                foreach ($breachedLeads as $lead) {
                    $activities[] = [
                        'tenant_id' => $this->tenantId,
                        'lead_id' => $lead->id,
                        'type' => 'sla_breach',
                        'description' => "Lead SLA breached. Agent {$lead->assigned_to_id} failed to contact in time.",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                
                DB::table('crm_activities')->insert($activities);
            });

        Log::info("MonitorLeadSlaJob completed for tenant {$this->tenantId}.");
    }
}
