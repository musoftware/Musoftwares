<?php

namespace App\Modules\CRMWhatsAppCampaigns\Jobs;

use App\Modules\CRMWhatsAppCampaigns\Services\CampaignAudienceResolver;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignDeliveryManager;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignSequenceEngine;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class StartCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    public function __construct(
        public WhatsAppCampaign $campaign,
    ) {
        $this->onQueue('wa-campaign-orchestrate');
    }

    public function handle(
        CampaignAudienceResolver $audienceResolver,
        CampaignDeliveryManager $deliveryManager,
        CampaignSequenceEngine $sequenceEngine,
    ): void {
        $campaign = $this->campaign->fresh();

        if (!$campaign->isRunning()) {
            return;
        }

        try {
            // Step 1: Resolve audience (if not already resolved)
            if ($campaign->audience && $campaign->total_recipients === 0) {
                $count = $audienceResolver->resolve($campaign->audience);
                WhatsAppCampaignEvent::record($campaign, 'audience_resolved', "Resolved {$count} recipients");
            }

            // Step 2: Generate delivery queue
            if ($campaign->deliveries()->count() === 0) {
                $count = $deliveryManager->generateDeliveryQueue($campaign);
                WhatsAppCampaignEvent::record($campaign, 'queue_generated', "Generated {$count} delivery records");

                if ($count === 0) {
                    app(WhatsAppCampaignService::class)->fail($campaign, 'No eligible recipients found.');
                    return;
                }
            }

            // Step 3: Start sequences if any
            $sequences = $campaign->sequences()->where('is_active', true)->get();
            if ($sequences->isNotEmpty()) {
                foreach ($sequences as $sequence) {
                    $sequenceEngine->startSequence($campaign, $sequence);
                }
            }

            // Step 4: Process first batch
            $processed = $deliveryManager->processBatch($campaign);
            WhatsAppCampaignEvent::record($campaign, 'batch_sent', "First batch: {$processed} messages queued");

            // Step 5: Schedule next batch
            $deliveryManager->scheduleNextBatch($campaign);

        } catch (\Exception $e) {
            Log::error("Campaign start failed: {$e->getMessage()}", [
                'campaign_id' => $campaign->id,
            ]);
            app(WhatsAppCampaignService::class)->fail($campaign, $e->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("StartCampaignJob failed: {$exception->getMessage()}", [
            'campaign_id' => $this->campaign->id,
        ]);
    }
}
