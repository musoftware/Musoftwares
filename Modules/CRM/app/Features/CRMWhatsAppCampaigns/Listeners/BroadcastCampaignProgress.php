<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted;
use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered;

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
            broadcast(new \Modules\CRM\app\Features\CRMWhatsAppInbox\Events\GenericBroadcastEvent(
                "crm.workspace.{$workspaceId}.campaigns",
                $eventName,
                $payload
            ))->toOthers();
        } catch (\Exception $e) {
            \Log::warning("Campaign broadcast failed: {$e->getMessage()}");
        }
    }
}
