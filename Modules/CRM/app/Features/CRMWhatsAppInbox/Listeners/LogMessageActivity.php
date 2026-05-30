<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageFailed;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationResolved;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppAccountConnected;
use Modules\CRM\Models\WhatsAppMessageLog;

class LogMessageActivity
{
    public function handle(
        WhatsAppMessageReceived|WhatsAppMessageSent|WhatsAppMessageFailed|WhatsAppConversationAssigned|WhatsAppConversationResolved|WhatsAppAccountConnected $event
    ): void {
        $action = match (true) {
            $event instanceof WhatsAppMessageReceived     => 'received',
            $event instanceof WhatsAppMessageSent         => 'sent',
            $event instanceof WhatsAppMessageFailed       => 'failed',
            $event instanceof WhatsAppConversationAssigned => 'assigned',
            $event instanceof WhatsAppConversationResolved => 'resolved',
            $event instanceof WhatsAppAccountConnected     => 'account_connected',
        };

        $data = [
            'workspace_id' => $event->workspaceId,
            'action'       => $action,
            'status'       => 'completed',
        ];

        if (property_exists($event, 'message')) {
            $data['message_id'] = $event->message->id;
            $data['conversation_id'] = $event->message->conversation_id;
        }

        if (property_exists($event, 'conversation')) {
            $data['conversation_id'] = $data['conversation_id'] ?? $event->conversation->id;
        }

        if ($event instanceof WhatsAppMessageFailed) {
            $data['status'] = 'failed';
            $data['error_message'] = $event->reason;
        }

        if ($event instanceof WhatsAppConversationAssigned) {
            $data['user_id'] = $event->agent->id;
            $data['metadata'] = [
                'assignment_type' => $event->assignmentType,
                'assigned_by'     => $event->assignedBy?->id,
            ];
        }

        WhatsAppMessageLog::create($data);
    }
}
