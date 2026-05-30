<?php

namespace Modules\CRM\Http\Controllers\WhatsAppCampaign;

use App\Http\Controllers\Controller;
use Modules\CRM\Http\Resources\WhatsAppCampaign\DeliveryResource;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\RetryCampaignDeliveryJob;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function index(Request $request, WhatsAppCampaign $campaign)
    {
        $query = $campaign->deliveries()->with('account');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json(DeliveryResource::collection(
            $query->latest()->paginate($request->per_page ?? 50)
        ));
    }

    public function retry(WhatsAppCampaignDelivery $delivery)
    {
        if (!$delivery->canRetry()) {
            return response()->json(['error' => 'Cannot retry this delivery.'], 422);
        }

        RetryCampaignDeliveryJob::dispatch($delivery);
        return response()->json(['message' => 'Retry queued.']);
    }

    public function retryAll(WhatsAppCampaign $campaign)
    {
        $count = $campaign->deliveries()->retryable()->count();

        $campaign->deliveries()->retryable()->cursor()->each(function ($delivery) {
            RetryCampaignDeliveryJob::dispatch($delivery);
        });

        return response()->json(['message' => "{$count} deliveries queued for retry."]);
    }

    public function stats(WhatsAppCampaign $campaign)
    {
        $stats = $campaign->deliveries()
            ->selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json($stats);
    }
}
