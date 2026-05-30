<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageSent;

class UpdateCrmTimelineOnMessage
{
    public function handle(WhatsAppMessageReceived|WhatsAppMessageSent $event): void
    {
        $message = $event->message;
        $conversation = $event->conversation;

        $eventName = $event instanceof WhatsAppMessageReceived
            ? 'whatsapp.message.received'
            : 'whatsapp.message.sent';

        activity()->log($eventName, $conversation, null, [
            'message_id'   => $message->id,
            'type'         => $message->type,
            'sender_type'  => $message->sender_type,
            'body_preview' => $message->getPreview(50),
        ]);

        // If conversation is linked to a lead, log on the lead's timeline too
        if ($conversation->lead_id && $conversation->lead) {
            activity()->log($eventName, $conversation->lead, null, [
                'conversation_id' => $conversation->id,
                'message_preview' => $message->getPreview(50),
                'contact_phone'   => $conversation->contact_phone,
            ]);
        }
    }
}
