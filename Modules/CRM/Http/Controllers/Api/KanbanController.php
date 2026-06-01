<?php

namespace Modules\CRM\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\CRM\Models\Lead;
use Illuminate\Http\JsonResponse;

class KanbanController extends Controller
{
    /**
     * Retrieve all pipeline stages and their respective leads for the current user's workspace/context.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $tenantId = session('tenant_id') ?? $request->user()->tenant_id;

        // Real pipeline stages mapping to ENUM values
        $stages = [
            ['id' => 1, 'key' => 'NEW', 'name' => 'New Leads', 'color' => '#3b82f6'],
            ['id' => 2, 'key' => 'NO_ANSWER', 'name' => 'No Answer', 'color' => '#ef4444'],
            ['id' => 3, 'key' => 'FOLLOW_UP', 'name' => 'Follow Up', 'color' => '#f59e0b'],
            ['id' => 4, 'key' => 'INTERESTED', 'name' => 'Interested', 'color' => '#8b5cf6'],
            ['id' => 5, 'key' => 'MEETING_SCHEDULED', 'name' => 'Meeting Scheduled', 'color' => '#0ea5e9'],
            ['id' => 6, 'key' => 'NEGOTIATION', 'name' => 'Negotiation', 'color' => '#10b981'],
        ];

        $isTeam = \Illuminate\Support\Facades\Auth::guard('crm_team')->check();
        $agentId = $isTeam ? \Illuminate\Support\Facades\Auth::guard('crm_team')->id() : $request->user()->id;
        $tenantId = session('tenant_id') ?? $request->user()->tenant_id;

        // Fetch leads assigned to the user
        $leads = Lead::where('tenant_id', $tenantId)
            ->where('assigned_to', $agentId)
            ->select('id', 'name', 'source', 'pipeline_stage', 'sla_breach_at', 'is_stale', 'call_attempts')
            ->get();

        // Map leads to stages
        $formattedStages = array_map(function ($stage) use ($leads) {
            $stageLeads = $leads->where('pipeline_stage', $stage['key'])->values()->map(function ($lead) use ($stage) {
                // Calculate dynamic score based on attempts and status
                $score = 100;
                if ($lead->is_stale) $score -= 50;
                if ($lead->call_attempts > 3) $score -= ($lead->call_attempts * 5);
                if ($lead->sla_breach_at && now()->greaterThan($lead->sla_breach_at)) $score -= 20;

                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'source' => $lead->source ?? 'Manual',
                    'score' => max(0, $score), // Real calculated score
                    'stageId' => $stage['id'],
                    'slaBreached' => $lead->sla_breach_at ? now()->greaterThan($lead->sla_breach_at) : false,
                ];
            });

            return [
                'id' => $stage['id'],
                'name' => $stage['name'],
                'color' => $stage['color'],
                'leads' => $stageLeads
            ];
        }, $stages);

        return response()->json($formattedStages);
    }

    /**
     * Update the pipeline stage of a specific lead (Drag and Drop trigger)
     */
    public function updateStage(Request $request, Lead $lead): JsonResponse
    {
        $request->validate([
            'stage_id' => 'required|integer',
            'position' => 'nullable|integer'
        ]);

        $stageMap = [
            1 => 'NEW',
            2 => 'NO_ANSWER',
            3 => 'FOLLOW_UP',
            4 => 'INTERESTED',
            5 => 'MEETING_SCHEDULED',
            6 => 'NEGOTIATION',
        ];

        if (array_key_exists($request->stage_id, $stageMap)) {
            $lead->pipeline_stage = $stageMap[$request->stage_id];
            $lead->save();
        }

        return response()->json(['status' => 'success']);
    }
}
