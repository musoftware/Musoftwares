<?php

namespace Modules\CRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonitorLeadSlaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting MonitorLeadSlaJob...');

        // Find leads where SLA has breached and they are not yet marked as stale
        $breachedLeads = DB::table('leads')
            ->whereNotNull('sla_breach_at')
            ->where('sla_breach_at', '<', now())
            ->where('is_stale', false)
            ->get();

        foreach ($breachedLeads as $lead) {
            DB::transaction(function () use ($lead) {
                // Mark lead as stale
                DB::table('leads')->where('id', $lead->id)->update([
                    'is_stale' => true,
                    'pipeline_stage' => 'NEW', // Optional: throw back into generic pool
                ]);

                // Create an alert/notification for the manager
                DB::table('crm_activities')->insert([
                    'lead_id' => $lead->id,
                    'type' => 'sla_breach',
                    'description' => "Lead SLA breached. Agent {$lead->assigned_to_id} failed to contact in time.",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
        }

        Log::info("MonitorLeadSlaJob completed. Marked {$breachedLeads->count()} leads as SLA breached.");
    }
}
