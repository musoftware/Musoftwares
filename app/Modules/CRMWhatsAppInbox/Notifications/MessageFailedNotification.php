<?php

namespace App\Modules\CRMWhatsAppInbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\CRM\Models\WhatsAppMessage;

class MessageFailedNotification extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppMessage $message, public string $reason) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'  => 'whatsapp.message_failed',
            'title' => 'Message Delivery Failed',
            'body'  => 'Failed to send message to ' . ($this->message->conversation->contact_name ?? $this->message->conversation->contact_phone) . ': ' . $this->reason,
            'data'  => [
                'conversation_id' => $this->message->conversation_id,
                'message_id'      => $this->message->id,
                'reason'          => $this->reason,
            ],
        ];
    }
}
