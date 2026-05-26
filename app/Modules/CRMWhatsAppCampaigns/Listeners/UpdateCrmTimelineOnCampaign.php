<?php

namespace App\Modules\CRMWhatsAppCampaigns\Listeners;

use App\Modules\CRMWhatsAppCampaigns\Events\WhatsAppCampaignMessageDelivered;

class UpdateCrmTimelineOnCampaign
{
    public function handle($event): void
    {
        if (!($event instanceof WhatsAppCampaignMessageDelivered)) {
            return;
        }

        $delivery = $event->delivery;
        $campaign = $event->campaign;

        // Add to CRM timeline if this is a lead
        if ($delivery->contactable_type === 'Modules\\CRM\\Models\\Lead' && $delivery->contactable_id) {
            try {
                activity()
                    ->performedOn($delivery->contactable)
                    ->withProperties([
                        'campaign_id'   => $campaign->id,
                        'campaign_name' => $campaign->name,
                        'message_type'  => $delivery->message_type,
                        'phone'         => $delivery->phone,
                        'status'        => $delivery->status,
                    ])
                    ->log('whatsapp_campaign.message_sent');
            } catch (\Exception $e) {
                \Log::warning("CRM timeline update failed: {$e->getMessage()}");
            }
        }
    }
}
