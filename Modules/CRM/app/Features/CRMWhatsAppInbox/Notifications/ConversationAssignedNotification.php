<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\CRM\Models\WhatsAppConversation;

class ConversationAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(public WhatsAppConversation $conversation) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'  => 'whatsapp.conversation_assigned',
            'title' => 'Conversation Assigned',
            'body'  => 'You have been assigned a WhatsApp conversation with ' . ($this->conversation->contact_name ?? $this->conversation->contact_phone),
            'data'  => [
                'conversation_id' => $this->conversation->id,
                'contact_name'    => $this->conversation->contact_name,
                'contact_phone'   => $this->conversation->contact_phone,
            ],
        ];
    }
}
