<?php

namespace App\Modules\CRMWhatsAppInbox\Listeners;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use App\Modules\CRMWhatsAppInbox\Jobs\EvaluateAutomationRulesJob;

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
