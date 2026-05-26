<?php

namespace App\Modules\CRMWhatsAppInbox\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\WhatsAppConversation;
use App\Models\User;

class WhatsAppConversationAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $workspaceId,
        public WhatsAppConversation $conversation,
        public User $agent,
        public ?User $assignedBy = null,
        public string $assignmentType = 'manual',
    ) {}
}
