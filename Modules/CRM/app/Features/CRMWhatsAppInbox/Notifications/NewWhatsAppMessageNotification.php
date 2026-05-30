<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\CRM\Models\WhatsAppMessage;

class NewWhatsAppMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppMessage $message) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'    => 'whatsapp.new_message',
            'title'   => 'New WhatsApp Message',
            'body'    => ($this->message->conversation->contact_name ?? $this->message->conversation->contact_phone) . ': ' . $this->message->getPreview(80),
            'data'    => [
                'conversation_id' => $this->message->conversation_id,
                'message_id'      => $this->message->id,
                'contact_name'    => $this->message->conversation->contact_name,
                'contact_phone'   => $this->message->conversation->contact_phone,
            ],
        ];
    }
}
