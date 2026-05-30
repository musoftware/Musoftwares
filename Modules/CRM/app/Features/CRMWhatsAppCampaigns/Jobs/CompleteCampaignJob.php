<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\CampaignAnalyticsAggregator;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompleteCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public WhatsAppCampaign $campaign,
    ) {
        $this->onQueue('wa-campaign-orchestrate');
    }

    public function handle(
        WhatsAppCampaignService $campaignService,
        CampaignAnalyticsAggregator $aggregator,
    ): void {
        $campaign = $this->campaign->fresh();

        if ($campaign->status !== 'running') {
            return;
        }

        // Final analytics aggregation
        $aggregator->aggregate($campaign);

        // Increment template usage
        if ($campaign->template) {
            $campaign->template->incrementUsage();
        }

        // Complete
        $campaignService->complete($campaign);
    }
}
