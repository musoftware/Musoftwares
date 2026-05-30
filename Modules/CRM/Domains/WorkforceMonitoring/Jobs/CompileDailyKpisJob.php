<?php

namespace Modules\CRM\Domains\WorkforceMonitoring\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CompileDailyKpisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $date;

    public function __construct(string $date)
    {
        $this->date = $date;
    }

    public function handle(): void
    {
        // For production, chunk through active agents
        User::whereHas('subscriptions', function ($q) {
            $q->where('module_id', 'crm-sales-staff');
        })->chunk(100, function ($agents) {
            $insertData = [];

            foreach ($agents as $agent) {
                // Compile metrics for the given date
                $callsMade = DB::table('crm_activities')
                    ->where('user_id', $agent->id)
                    ->where('type', 'call')
                    ->whereDate('created_at', $this->date)
                    ->count();

                $leadsClosed = DB::table('leads')
                    ->where('assigned_to_id', $agent->id)
                    ->where('pipeline_stage', 'WON')
                    ->whereDate('updated_at', $this->date)
                    ->count();

                $totalAssigned = DB::table('leads')
                    ->where('assigned_to_id', $agent->id)
                    ->whereDate('reassigned_at', $this->date)
                    ->count();

                $conversionRate = $totalAssigned > 0 ? ($leadsClosed / $totalAssigned) * 100 : 0;

                $insertData[] = [
                    'tenant_id' => $agent->tenant_id,
                    'branch_id' => $agent->branch_id,
                    'user_id' => $agent->id,
                    'date' => $this->date,
                    'calls_made' => $callsMade,
                    'leads_closed' => $leadsClosed,
                    'total_assigned' => $totalAssigned,
                    'conversion_rate' => round($conversionRate, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($insertData)) {
                // Upsert to handle hourly re-compilation cleanly
                DB::table('daily_agent_kpis')->upsert(
                    $insertData,
                    ['tenant_id', 'user_id', 'date'],
                    ['calls_made', 'leads_closed', 'total_assigned', 'conversion_rate', 'updated_at']
                );
            }
        });
    }
}
