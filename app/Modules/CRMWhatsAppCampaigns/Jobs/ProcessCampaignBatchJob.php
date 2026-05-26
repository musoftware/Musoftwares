<?php

namespace App\Modules\CRMWhatsAppCampaigns\Jobs;

use App\Modules\CRMWhatsAppCampaigns\Services\CampaignDeliveryManager;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampaignBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [10, 30];

    public function __construct(
        public WhatsAppCampaign $campaign,
    ) {
        $this->onQueue('wa-campaign-delivery');
    }

    public function handle(CampaignDeliveryManager $deliveryManager): void
    {
        $campaign = $this->campaign->fresh();

        if (!$campaign->isRunning()) {
            return;
        }

        $processed = $deliveryManager->processBatch($campaign);

        if ($processed > 0) {
            $deliveryManager->scheduleNextBatch($campaign);
        } else {
            // Check if campaign is complete
            if ($deliveryManager->isComplete($campaign)) {
                CompleteCampaignJob::dispatch($campaign);
            }
        }
    }
}
