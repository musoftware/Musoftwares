<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Events;

use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppCampaignMessageDelivered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public WhatsAppCampaign $campaign,
        public WhatsAppCampaignDelivery $delivery,
    ) {}
}
