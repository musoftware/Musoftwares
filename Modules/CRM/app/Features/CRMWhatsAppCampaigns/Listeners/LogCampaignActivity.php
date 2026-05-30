<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Listeners;

class LogCampaignActivity
{
    public function handle($event): void
    {
        $campaign = $event->campaign;

        $action = match (get_class($event)) {
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCreated::class   => 'campaign.created',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted::class   => 'campaign.started',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted::class => 'campaign.completed',
            \Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events\WhatsAppCampaignFailed::class    => 'campaign.failed',
            default => 'campaign.updated',
        };

        try {
            activity()
                ->performedOn($campaign)
                ->causedBy(auth()->user())
                ->withProperties([
                    'campaign_id'   => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'status'        => $campaign->status,
                    'workspace_id'  => $event->workspaceId,
                ])
                ->log($action);
        } catch (\Exception $e) {
            \Log::warning("Campaign activity log failed: {$e->getMessage()}");
        }
    }
}
