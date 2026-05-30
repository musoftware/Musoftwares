<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\AggregateCampaignAnalyticsJob;

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
