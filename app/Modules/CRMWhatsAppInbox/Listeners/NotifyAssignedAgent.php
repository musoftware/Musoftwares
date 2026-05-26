<?php

namespace App\Modules\CRMWhatsAppInbox\Listeners;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageFailed;
use App\Modules\CRMWhatsAppInbox\Notifications\NewWhatsAppMessageNotification;
use App\Modules\CRMWhatsAppInbox\Notifications\ConversationAssignedNotification;
use App\Modules\CRMWhatsAppInbox\Notifications\UnassignedConversationAlert;
use App\Modules\CRMWhatsAppInbox\Notifications\MessageFailedNotification;
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
