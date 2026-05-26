<?php

namespace App\Modules\CRMWhatsAppCampaigns\Events;

use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignSequence;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppCampaignSequenceTriggered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public WhatsAppCampaign $campaign,
        public WhatsAppCampaignSequence $sequence,
    ) {}
}
