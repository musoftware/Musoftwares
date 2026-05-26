<?php

namespace App\Modules\CRMWhatsAppCampaigns\Listeners;

use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignFailed;
use App\Modules\CRMWhatsAppCampaigns\Notifications\CampaignCompletedNotification;
use App\Modules\CRMWhatsAppCampaigns\Notifications\CampaignFailedNotification;
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
