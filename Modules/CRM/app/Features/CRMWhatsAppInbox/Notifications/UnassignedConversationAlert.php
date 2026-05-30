<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\CRM\Models\WhatsAppConversation;

class UnassignedConversationAlert extends Notification
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
            'type'  => 'whatsapp.unassigned_conversation',
            'title' => 'Unassigned Conversation',
            'body'  => 'New unassigned WhatsApp conversation from ' . ($this->conversation->contact_name ?? $this->conversation->contact_phone),
            'data'  => [
                'conversation_id' => $this->conversation->id,
                'contact_phone'   => $this->conversation->contact_phone,
            ],
        ];
    }
}
