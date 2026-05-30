<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs\EvaluateAutomationRulesJob;

class EvaluateAutomationsOnEvent
{
    public function handle(WhatsAppMessageReceived $event): void
    {
        EvaluateAutomationRulesJob::dispatch(
            $event->conversation,
            'message.received',
            $event->message
        )->onQueue('whatsapp-automation');
    }
}
