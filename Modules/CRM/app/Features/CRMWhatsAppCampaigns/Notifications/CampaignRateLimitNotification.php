<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CampaignRateLimitNotification extends Notification
{
    use Queueable;

    public function __construct(public int $workspaceId, public string $limitKey, public int $used, public int $limit) {}

    public function via($notifiable): array { return ['database']; }

    public function toArray($notifiable): array
    {
        return ['type' => 'campaign_rate_limit', 'workspace_id' => $this->workspaceId, 'limit_key' => $this->limitKey, 'used' => $this->used, 'limit' => $this->limit, 'message' => "WhatsApp campaign message limit approaching: {$this->used}/{$this->limit}"];
    }
}
