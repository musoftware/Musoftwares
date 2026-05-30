<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use Modules\CRM\Http\Requests\WhatsAppCampaign\StoreCampaignRequest;
use Modules\CRM\Http\Requests\WhatsAppCampaign\UpdateCampaignRequest;
use Modules\CRM\Http\Requests\WhatsAppCampaign\CampaignFilterRequest;
use Modules\CRM\Http\Resources\WhatsAppCampaign\CampaignResource;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct(
        protected WhatsAppCampaignService $campaignService
    ) {}

    public function index(CampaignFilterRequest $request)
    {
        $workspaceId = session('crm_workspace_id');
        $campaigns = $this->campaignService->list($workspaceId, $request->validated());
        $dashboard = $this->campaignService->getDashboard($workspaceId);

        return Inertia::render('CRM/WhatsAppCampaigns/Index', [
            'campaigns' => CampaignResource::collection($campaigns),
            'dashboard' => $dashboard,
            'filters'   => $request->validated(),
        ]);
    }

    public function create()
    {
        return Inertia::render('CRM/WhatsAppCampaigns/Create');
    }

    public function store(StoreCampaignRequest $request)
    {
        $campaign = $this->campaignService->create(
            session('crm_workspace_id'),
            $request->validated()
        );

        return redirect()
            ->route('crm.whatsapp-campaigns.show', $campaign->id)
            ->with('success', __('crm.campaign_created'));
    }

    public function show(WhatsAppCampaign $campaign)
    {
        $campaign->load(['template', 'audience', 'sequences.steps', 'creator', 'events' => fn($q) => $q->latest()->limit(20)]);

        return Inertia::render('CRM/WhatsAppCampaigns/Show', [
            'campaign' => (new CampaignResource($campaign))->resolve(),
        ]);
    }

    public function edit(WhatsAppCampaign $campaign)
    {
        $campaign->load(['template', 'audience', 'sequences.steps']);

        return Inertia::render('CRM/WhatsAppCampaigns/Edit', [
            'campaign' => (new CampaignResource($campaign))->resolve(),
        ]);
    }

    public function update(UpdateCampaignRequest $request, WhatsAppCampaign $campaign)
    {
        $this->campaignService->update($campaign, $request->validated());

        return redirect()->back()->with('success', __('crm.campaign_updated'));
    }

    public function destroy(WhatsAppCampaign $campaign)
    {
        if (!$campaign->isDraft()) {
            return redirect()->back()->with('error', __('crm.only_draft_campaigns_deleted'));
        }

        $campaign->delete();
        return redirect()->route('crm.whatsapp-campaigns.index')->with('success', __('crm.campaign_deleted'));
    }

    // ── Lifecycle Actions ────────────────────────────────────────

    public function start(WhatsAppCampaign $campaign)
    {
        $this->campaignService->start($campaign);
        return redirect()->back()->with('success', __('crm.campaign_started'));
    }

    public function schedule(Request $request, WhatsAppCampaign $campaign)
    {
        $request->validate(['scheduled_at' => 'required|date|after:now']);
        $this->campaignService->schedule($campaign, \Carbon\Carbon::parse($request->scheduled_at));
        return redirect()->back()->with('success', __('crm.campaign_scheduled'));
    }

    public function pause(WhatsAppCampaign $campaign)
    {
        $this->campaignService->pause($campaign);
        return redirect()->back()->with('success', __('crm.campaign_paused'));
    }

    public function resume(WhatsAppCampaign $campaign)
    {
        $this->campaignService->resume($campaign);
        return redirect()->back()->with('success', __('crm.campaign_resumed'));
    }

    public function cancel(WhatsAppCampaign $campaign)
    {
        $this->campaignService->cancel($campaign);
        return redirect()->back()->with('success', __('crm.campaign_cancelled'));
    }

    public function duplicate(WhatsAppCampaign $campaign)
    {
        $new = $this->campaignService->duplicate($campaign);
        return redirect()->route('crm.whatsapp-campaigns.show', $new->id)->with('success', __('crm.campaign_duplicated'));
    }
}
