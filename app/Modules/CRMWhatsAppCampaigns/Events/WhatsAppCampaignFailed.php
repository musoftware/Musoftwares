<?php

namespace App\Modules\CRMWhatsAppCampaigns\Events;

use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppCampaignFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public WhatsAppCampaign $campaign,
        public string $reason,
    ) {}
}
