<?php

namespace App\Modules\CRMWhatsAppInbox\Listeners;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppSlaEngine;

class UpdateConversationMetrics
{
    public function __construct(
        protected WhatsAppSlaEngine $slaEngine,
    ) {}

    public function handle(WhatsAppMessageReceived|WhatsAppMessageSent $event): void
    {
        // If an agent sent a message and it's the first response, record it
        if ($event instanceof WhatsAppMessageSent && $event->message->sender_type === 'agent') {
            $conversation = $event->conversation;
            if (!$conversation->first_response_at) {
                $this->slaEngine->recordFirstResponse($conversation);
            }
        }
    }
}
