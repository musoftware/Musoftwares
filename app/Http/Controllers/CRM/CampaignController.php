<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use Modules\CRM\Models\Campaign;
use App\Services\CampaignService;
use App\Http\Requests\Admin\Campaign\StoreCampaignRequest;
use App\Http\Requests\Admin\Campaign\UpdateCampaignRequest;
use App\Http\Requests\Admin\Campaign\GenerateAICampaignRequest;
use App\Http\Resources\CampaignResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    public function __construct(
        protected CampaignService $campaignService
    ) {}
    public function index()
    {
        $campaigns = Campaign::withCount('recipients')->latest()->paginate(20)->through(fn($c) => (new CampaignResource($c))->resolve());
        return Inertia::render('CRM/Campaigns/Index', [
            'campaigns' => $campaigns
        ]);
    }

    public function store(StoreCampaignRequest $request)
    {
        $campaign = $this->campaignService->createCampaign($request->validated());
        
        return redirect()->route('admin.campaigns.show', $campaign->id)->with('success', 'Campaign created successfully.');
    }

    public function show(Campaign $campaign)
    {
        $campaign->loadCount('recipients');
        return Inertia::render('CRM/Campaigns/Show', [
            'campaign' => (new CampaignResource($campaign))->resolve()
        ]);
    }

    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $this->campaignService->updateCampaign($campaign, $request->validated());

        return redirect()->back()->with('success', 'Campaign content saved.');
    }

    public function destroy(Campaign $campaign)
    {
        $this->campaignService->deleteCampaign($campaign);
        return redirect()->route('admin.campaigns.index')->with('success', 'Campaign deleted.');
    }

    // -- AI Generation --
    public function generateAIContent(GenerateAICampaignRequest $request)
    {
        try {
            $generated = $this->campaignService->generateAIContent(
                $request->input('context'),
                $request->input('tone'),
                $request->input('type')
            );
            return response()->json(['content' => $generated]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // -- Status Management --
    public function schedule(Request $request, Campaign $campaign)
    {
        $this->campaignService->scheduleCampaign($campaign);
        return redirect()->back()->with('success', 'Campaign scheduled.');
    }

    public function pause(Campaign $campaign)
    {
        $this->campaignService->pauseCampaign($campaign);
        return redirect()->back()->with('success', 'Campaign paused.');
    }

    public function resume(Campaign $campaign)
    {
        $this->campaignService->resumeCampaign($campaign);
        return redirect()->back()->with('success', 'Campaign resumed.');
    }
}
