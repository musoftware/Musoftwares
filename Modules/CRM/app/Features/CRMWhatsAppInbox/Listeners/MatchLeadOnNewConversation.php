<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppInboxService;

class MatchLeadOnNewConversation
{
    public function __construct(
        protected WhatsAppInboxService $inboxService,
    ) {}

    public function handle(WhatsAppMessageReceived $event): void
    {
        $conversation = $event->conversation;

        // Only match if not already linked
        if (!$conversation->lead_id) {
            $this->inboxService->matchLeadOrCustomer($conversation, $conversation->contact_phone);
        }
    }
}
