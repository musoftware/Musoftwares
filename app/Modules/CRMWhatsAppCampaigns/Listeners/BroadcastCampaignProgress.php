<?php

namespace App\Modules\CRMWhatsAppCampaigns\Listeners;

use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted;
use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered;

class BroadcastCampaignProgress
{
    public function handle($event): void
    {
        $workspaceId = $event->workspaceId;
        $campaign = $event->campaign;

        $payload = [
            'campaign_id' => $campaign->id,
            'name'        => $campaign->name,
            'status'      => $campaign->status,
            'progress'    => $campaign->getProgressPercentage(),
            'sent'        => $campaign->sent_count,
            'delivered'   => $campaign->delivered_count,
            'failed'      => $campaign->failed_count,
            'total'       => $campaign->total_recipients,
        ];

        $eventName = match (true) {
            $event instanceof WhatsAppCampaignStarted          => 'CampaignStarted',
            $event instanceof WhatsAppCampaignCompleted        => 'CampaignCompleted',
            $event instanceof WhatsAppCampaignMessageDelivered => 'CampaignProgress',
            default                                            => 'CampaignUpdated',
        };

        try {
            broadcast(new \App\Modules\CRMWhatsAppInbox\Events\GenericBroadcastEvent(
                "crm.workspace.{$workspaceId}.campaigns",
                $eventName,
                $payload
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning("Campaign broadcast failed: {$e->getMessage()}");
        }
    }
}
