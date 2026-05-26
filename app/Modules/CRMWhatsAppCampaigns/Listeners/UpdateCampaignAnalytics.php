<?php

namespace App\Modules\CRMWhatsAppCampaigns\Listeners;

use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered;
use App\Modules\CRMWhatsAppCampaigns\Jobs\AggregateCampaignAnalyticsJob;

class UpdateCampaignAnalytics
{
    protected static int $messageCounter = 0;

    public function handle($event): void
    {
        if (!($event instanceof WhatsAppCampaignMessageDelivered)) {
            return;
        }

        // Batch analytics — aggregate every 50 messages to reduce DB writes
        self::$messageCounter++;

        if (self::$messageCounter % 50 === 0) {
            AggregateCampaignAnalyticsJob::dispatch();
        }
    }
}
