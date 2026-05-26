<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\WhatsAppCampaign\StoreCampaignAutomationRequest;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Http\Request;

class CampaignAutomationController extends Controller
{
    public function index()
    {
        $automations = WhatsAppCampaign::whereNotNull('trigger_event')
            ->with('template', 'audience')
            ->latest()
            ->paginate(20);

        return response()->json($automations);
    }

    public function store(StoreCampaignAutomationRequest $request)
    {
        $campaign = WhatsAppCampaign::create(array_merge($request->validated(), [
            'workspace_id' => session('crm_workspace_id'),
            'type'         => 'transactional',
            'status'       => 'draft',
            'created_by'   => auth()->id(),
        ]));

        return response()->json($campaign, 201);
    }

    public function update(StoreCampaignAutomationRequest $request, WhatsAppCampaign $campaign)
    {
        $campaign->update($request->validated());
        return response()->json($campaign);
    }

    public function destroy(WhatsAppCampaign $campaign)
    {
        $campaign->delete();
        return response()->json(null, 204);
    }

    public function toggle(WhatsAppCampaign $campaign)
    {
        $newStatus = $campaign->status === 'running' ? 'paused' : 'running';
        $campaign->update(['status' => $newStatus]);
        return response()->json(['status' => $newStatus]);
    }
}
