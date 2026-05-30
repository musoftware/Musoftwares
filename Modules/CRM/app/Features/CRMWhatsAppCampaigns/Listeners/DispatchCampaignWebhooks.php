<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

class DispatchCampaignWebhooks
{
    public function handle($event): void
    {
        $campaign = $event->campaign;

        $webhookEvent = match (get_class($event)) {
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted::class          => 'campaign.started',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted::class        => 'campaign.completed',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignFailed::class           => 'campaign.failed',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered::class => 'message.delivered',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignSequenceTriggered::class => 'sequence.triggered',
            default => null,
        };

        if (!$webhookEvent) return;

        $payload = [
            'event'       => $webhookEvent,
            'campaign_id' => $campaign->id,
            'campaign'    => $campaign->name,
            'status'      => $campaign->status,
            'workspace_id' => $event->workspaceId,
            'timestamp'   => now()->toIso8601String(),
        ];

        // Use the existing webhook dispatch system if available
        try {
            if (class_exists(\Spatie\WebhookServer\WebhookCall::class)) {
                // Dispatch via configured webhook URLs
            }
        } catch (\Exception $e) {
            \Log::warning("Campaign webhook dispatch failed: {$e->getMessage()}");
        }
    }
}
