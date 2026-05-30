<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationResolved;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppRealtimeBroadcaster;

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
