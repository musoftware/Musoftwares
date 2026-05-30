<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Notifications;

use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CampaignFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppCampaign $campaign, public string $reason) {}

    public function via($notifiable): array { return ['database', 'mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Campaign Failed: {$this->campaign->name}")
            ->greeting('Campaign Failed ⚠️')
            ->line("Your WhatsApp campaign \"{$this->campaign->name}\" has failed.")
            ->line("Reason: {$this->reason}")
            ->line("Sent before failure: {$this->campaign->sent_count}");
    }

    public function toArray($notifiable): array
    {
        return ['type' => 'campaign_failed', 'campaign_id' => $this->campaign->id, 'campaign_name' => $this->campaign->name, 'reason' => $this->reason];
    }
}
