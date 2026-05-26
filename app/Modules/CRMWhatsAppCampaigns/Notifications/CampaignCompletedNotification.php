<?php

namespace App\Modules\CRMWhatsAppCampaigns\Notifications;

use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class CampaignCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppCampaign $campaign) {}

    public function via($notifiable): array { return ['database', 'mail']; }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Campaign Completed: {$this->campaign->name}")
            ->greeting('Campaign Completed! 🎉')
            ->line("Your WhatsApp campaign \"{$this->campaign->name}\" has finished.")
            ->line("Sent: {$this->campaign->sent_count} | Delivered: {$this->campaign->delivered_count} | Failed: {$this->campaign->failed_count}")
            ->line("Delivery Rate: {$this->campaign->getDeliveryRate()}% | Read Rate: {$this->campaign->getReadRate()}%");
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'campaign_completed', 'campaign_id' => $this->campaign->id,
            'campaign_name' => $this->campaign->name, 'sent' => $this->campaign->sent_count,
            'delivered' => $this->campaign->delivered_count, 'failed' => $this->campaign->failed_count,
        ];
    }
}
