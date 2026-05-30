<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs;

use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecoverFailedCampaignDeliveriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct()
    {
        $this->onQueue('wa-campaign-retry');
    }

    public function handle(): void
    {
        WhatsAppCampaignDelivery::withoutGlobalScopes()
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereColumn('retry_count', '<', 'max_retries')
            ->cursor()
            ->each(function (WhatsAppCampaignDelivery $delivery) {
                // Only retry if campaign is still running
                if ($delivery->campaign?->isRunning()) {
                    RetryCampaignDeliveryJob::dispatch($delivery);
                }
            });
    }
}
