<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignFailed;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Notifications\CampaignCompletedNotification;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Notifications\CampaignFailedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyCampaignManagers
{
    public function handle($event): void
    {
        $campaign = $event->campaign;
        $creator = $campaign->creator;

        if (!$creator) return;

        try {
            match (true) {
                $event instanceof WhatsAppCampaignCompleted => $creator->notify(new CampaignCompletedNotification($campaign)),
                $event instanceof WhatsAppCampaignFailed    => $creator->notify(new CampaignFailedNotification($campaign, $event->reason)),
                default => null,
            };
        } catch (\Exception $e) {
            \Log::warning("Campaign notification failed: {$e->getMessage()}");
        }
    }
}
