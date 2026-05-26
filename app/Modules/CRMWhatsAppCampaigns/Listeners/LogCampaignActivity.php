<?php

namespace App\Modules\CRMWhatsAppCampaigns\Listeners;

class LogCampaignActivity
{
    public function handle($event): void
    {
        $campaign = $event->campaign;

        $action = match (get_class($event)) {
            \App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCreated::class   => 'campaign.created',
            \App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignStarted::class   => 'campaign.started',
            \App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignCompleted::class => 'campaign.completed',
            \App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignFailed::class    => 'campaign.failed',
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
