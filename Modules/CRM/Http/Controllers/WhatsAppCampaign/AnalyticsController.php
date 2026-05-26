<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignAnalyticsAggregator;
use Modules\CRM\Http\Resources\WhatsAppCampaign\CampaignAnalyticsResource;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function __construct(protected CampaignAnalyticsAggregator $aggregator) {}

    public function overview(Request $request)
    {
        $period = $request->period ?? 'month';
        $overview = $this->aggregator->getOverview(session('crm_workspace_id'), $period);

        return Inertia::render('CRM/WhatsAppCampaigns/Analytics/Overview', [
            'overview' => $overview,
            'period'   => $period,
        ]);
    }

    public function campaign(WhatsAppCampaign $campaign)
    {
        $detail = $this->aggregator->getCampaignDetail($campaign);
        return response()->json($detail);
    }

    public function compare(Request $request)
    {
        $request->validate(['campaign_a' => 'required|exists:crm_wa_campaigns,id', 'campaign_b' => 'required|exists:crm_wa_campaigns,id']);

        $a = WhatsAppCampaign::findOrFail($request->campaign_a);
        $b = WhatsAppCampaign::findOrFail($request->campaign_b);

        return response()->json($this->aggregator->compare($a, $b));
    }

    public function trends(Request $request)
    {
        $campaign = WhatsAppCampaign::findOrFail($request->campaign_id);
        $analytics = $campaign->analytics()->orderBy('date')->orderBy('hour')->get();

        return response()->json(CampaignAnalyticsResource::collection($analytics));
    }

    public function export(WhatsAppCampaign $campaign)
    {
        $detail = $this->aggregator->getCampaignDetail($campaign);
        return response()->json($detail);
    }
}
