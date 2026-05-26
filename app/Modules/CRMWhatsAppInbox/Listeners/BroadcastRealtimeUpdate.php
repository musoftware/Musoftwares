<?php

namespace App\Modules\CRMWhatsAppInbox\Listeners;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppConversationResolved;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppRealtimeBroadcaster;

class BroadcastRealtimeUpdate
{
    public function __construct(
        protected WhatsAppRealtimeBroadcaster $broadcaster,
    ) {}

    public function handle(WhatsAppMessageReceived|WhatsAppMessageSent|WhatsAppConversationAssigned|WhatsAppConversationResolved $event): void
    {
        match (true) {
            $event instanceof WhatsAppMessageReceived,
            $event instanceof WhatsAppMessageSent
                => $this->broadcaster->broadcastNewMessage($event->message, $event->conversation),

            $event instanceof WhatsAppConversationAssigned
                => $this->broadcaster->broadcastAssignment($event->conversation, $event->agent),

            $event instanceof WhatsAppConversationResolved
                => $this->broadcaster->broadcastConversationUpdate($event->conversation),
        };
    }
}
