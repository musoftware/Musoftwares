<?php

namespace App\Modules\CRMWhatsAppInbox\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\WhatsAppAccount;

class WhatsAppAccountConnected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public WhatsAppAccount $account,
    ) {}
}
