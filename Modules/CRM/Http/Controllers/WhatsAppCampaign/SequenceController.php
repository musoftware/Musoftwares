<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\WhatsAppCampaign\StoreSequenceRequest;
use Modules\CRM\Http\Requests\WhatsAppCampaign\StoreSequenceStepRequest;
use Modules\CRM\Http\Resources\WhatsAppCampaign\SequenceResource;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignSequence;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;
use Illuminate\Http\Request;

class SequenceController extends Controller
{
    public function index(WhatsAppCampaign $campaign)
    {
        $sequences = $campaign->sequences()->with('steps')->get();
        return response()->json(SequenceResource::collection($sequences));
    }

    public function store(StoreSequenceRequest $request, WhatsAppCampaign $campaign)
    {
        $sequence = $campaign->sequences()->create(array_merge($request->validated(), [
            'workspace_id' => $campaign->workspace_id,
        ]));

        return response()->json(new SequenceResource($sequence), 201);
    }

    public function update(StoreSequenceRequest $request, WhatsAppCampaignSequence $sequence)
    {
        $sequence->update($request->validated());
        return response()->json(new SequenceResource($sequence));
    }

    public function destroy(WhatsAppCampaignSequence $sequence)
    {
        $sequence->delete();
        return response()->json(null, 204);
    }

    public function toggle(WhatsAppCampaignSequence $sequence)
    {
        $sequence->update(['is_active' => !$sequence->is_active]);
        return response()->json(['is_active' => $sequence->is_active]);
    }

    // ── Steps ────────────────────────────────────────────────────

    public function storeStep(StoreSequenceStepRequest $request, WhatsAppCampaignSequence $sequence)
    {
        $step = $sequence->steps()->create(array_merge($request->validated(), [
            'workspace_id' => $sequence->workspace_id,
        ]));

        $sequence->update(['total_steps' => $sequence->steps()->count()]);

        return response()->json($step, 201);
    }

    public function updateStep(StoreSequenceStepRequest $request, WhatsAppCampaignSequenceStep $step)
    {
        $step->update($request->validated());
        return response()->json($step);
    }

    public function destroyStep(WhatsAppCampaignSequenceStep $step)
    {
        $sequence = $step->sequence;
        $step->delete();
        $sequence->update(['total_steps' => $sequence->steps()->count()]);
        return response()->json(null, 204);
    }

    public function reorderSteps(Request $request, WhatsAppCampaignSequence $sequence)
    {
        $request->validate(['steps' => 'required|array', 'steps.*.id' => 'required|integer', 'steps.*.step_order' => 'required|integer']);

        foreach ($request->steps as $data) {
            WhatsAppCampaignSequenceStep::where('id', $data['id'])
                ->where('sequence_id', $sequence->id)
                ->update(['step_order' => $data['step_order']]);
        }

        return response()->json(['message' => 'Steps reordered.']);
    }
}
