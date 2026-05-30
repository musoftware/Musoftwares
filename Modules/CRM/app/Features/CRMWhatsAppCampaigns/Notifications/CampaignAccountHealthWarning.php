<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Notifications;

use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampaignAccountHealthWarning extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppAccount $account, public string $issue) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array
    {
        return ['type' => 'campaign_account_health', 'account_id' => $this->account->id, 'phone' => $this->account->phone_number, 'issue' => $this->issue];
    }
}
