<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\WhatsAppMessage;

class WhatsAppMessageFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public WhatsAppMessage $message,
        public string $reason,
    ) {}
}
