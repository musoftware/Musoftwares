<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\CRM\Models\WhatsAppConversation;

class SlaBreachNotification extends Notification
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
            'type'  => 'whatsapp.sla_breach',
            'title' => '⚠️ SLA Breach',
            'body'  => 'SLA breached for conversation with ' . ($this->conversation->contact_name ?? $this->conversation->contact_phone) . '. Response is overdue.',
            'data'  => [
                'conversation_id' => $this->conversation->id,
                'contact_name'    => $this->conversation->contact_name,
                'sla_due_at'      => $this->conversation->sla_due_at?->toIso8601String(),
                'priority'        => $this->conversation->priority,
            ],
        ];
    }
}
