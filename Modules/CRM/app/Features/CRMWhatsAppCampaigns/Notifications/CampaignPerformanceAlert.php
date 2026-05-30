<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Notifications;

use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampaignPerformanceAlert extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppCampaign $campaign, public string $alert) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array
    {
        return ['type' => 'campaign_performance', 'campaign_id' => $this->campaign->id, 'campaign_name' => $this->campaign->name, 'alert' => $this->alert, 'delivery_rate' => $this->campaign->getDeliveryRate(), 'read_rate' => $this->campaign->getReadRate()];
    }
}
