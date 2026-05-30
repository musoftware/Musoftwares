<?php

namespace Modules\CRM\Domains\WorkforceMonitoring\Actions;

use Illuminate\Support\Facades\DB;
use Modules\CRM\Domains\WorkforceMonitoring\DTOs\AgentKpiData;

class CalculateKpisAction
{
    /**
     * Calculate KPIs for a specific agent across a date range.
     */
    public function execute(int $agentId, string $startDate, string $endDate): AgentKpiData
    {
        // Calculate total calls made
        $callsMade = DB::table('crm_activities')
            ->where('user_id', $agentId)
            ->where('type', 'call')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        // Calculate closed/won leads
        $leadsClosed = DB::table('leads')
            ->where('assigned_to_id', $agentId)
            ->where('pipeline_stage', 'WON')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // Total leads handled (for conversion rate)
        $totalAssigned = DB::table('leads')
            ->where('assigned_to_id', $agentId)
            ->whereBetween('reassigned_at', [$startDate, $endDate])
            ->count();

        $conversionRate = $totalAssigned > 0 ? ($leadsClosed / $totalAssigned) * 100 : 0;

        return new AgentKpiData(
            agentId: $agentId,
            callsMade: $callsMade,
            leadsClosed: $leadsClosed,
            conversionRate: round($conversionRate, 2)
        );
    }
}
