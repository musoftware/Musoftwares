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

        // In a real app, stages might come from a config or database
        $stages = [
            ['id' => 1, 'key' => 'NEW', 'name' => 'New Leads', 'color' => '#3b82f6'],
            ['id' => 2, 'key' => 'FOLLOW_UP', 'name' => 'Follow Up', 'color' => '#f59e0b'],
            ['id' => 3, 'key' => 'INTERESTED', 'name' => 'Interested', 'color' => '#8b5cf6'],
            ['id' => 4, 'key' => 'NEGOTIATION', 'name' => 'Negotiation', 'color' => '#10b981'],
        ];

        // Fetch leads assigned to the user
        $leads = Lead::where('tenant_id', $tenantId)
            ->where('assigned_to_id', $userId)
            ->select('id', 'name', 'source', 'pipeline_stage', 'sla_breach_at')
            ->get();

        // Map leads to stages
        $formattedStages = array_map(function ($stage) use ($leads) {
            $stageLeads = $leads->where('pipeline_stage', $stage['key'])->values()->map(function ($lead) use ($stage) {
                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'source' => $lead->source ?? 'Manual',
                    'score' => 0, // Mock score for now
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
            2 => 'FOLLOW_UP',
            3 => 'INTERESTED',
            4 => 'NEGOTIATION',
        ];

        if (array_key_exists($request->stage_id, $stageMap)) {
            $lead->pipeline_stage = $stageMap[$request->stage_id];
            $lead->save();
        }

        return response()->json(['status' => 'success']);
    }
}
