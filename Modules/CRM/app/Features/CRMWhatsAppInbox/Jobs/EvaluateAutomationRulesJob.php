<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppAutomationEngine;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateAutomationRulesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public WhatsAppConversation $conversation,
        public string $triggerEvent,
        public ?WhatsAppMessage $message = null,
    ) {
        $this->onQueue('whatsapp-automation');
    }

    public function handle(WhatsAppAutomationEngine $engine): void
    {
        $engine->evaluate($this->conversation, $this->triggerEvent, $this->message);
    }
}
