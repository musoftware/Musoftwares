<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageFailed;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications\NewWhatsAppMessageNotification;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications\ConversationAssignedNotification;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications\UnassignedConversationAlert;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Notifications\MessageFailedNotification;
use Illuminate\Support\Facades\DB;

class NotifyAssignedAgent
{
    public function handle(WhatsAppMessageReceived|WhatsAppConversationAssigned|WhatsAppMessageFailed $event): void
    {
        match (true) {
            $event instanceof WhatsAppMessageReceived     => $this->notifyOnNewMessage($event),
            $event instanceof WhatsAppConversationAssigned => $this->notifyOnAssignment($event),
            $event instanceof WhatsAppMessageFailed       => $this->notifyOnFailure($event),
        };
    }

    protected function notifyOnNewMessage(WhatsAppMessageReceived $event): void
    {
        $conversation = $event->conversation;

        if ($conversation->assignedAgent) {
            $conversation->assignedAgent->notify(new NewWhatsAppMessageNotification($event->message));
        } else {
            // Notify all workspace managers about unassigned conversation
            $managerIds = DB::table('crm_workspace_users')
                ->where('workspace_id', $conversation->workspace_id)
                ->where('is_active', true)
                ->pluck('user_id');

            foreach ($managerIds as $managerId) {
                $user = \App\Models\User::find($managerId);
                if ($user) {
                    $user->notify(new UnassignedConversationAlert($conversation));
                }
            }
        }
    }

    protected function notifyOnAssignment(WhatsAppConversationAssigned $event): void
    {
        $event->agent->notify(new ConversationAssignedNotification($event->conversation));
    }

    protected function notifyOnFailure(WhatsAppMessageFailed $event): void
    {
        $conversation = $event->message->conversation;
        if ($conversation->assignedAgent) {
            $conversation->assignedAgent->notify(new MessageFailedNotification($event->message, $event->reason));
        }
    }
}
