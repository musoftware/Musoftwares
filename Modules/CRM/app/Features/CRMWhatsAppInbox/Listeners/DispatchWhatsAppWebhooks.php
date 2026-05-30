<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Listeners;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationResolved;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppAccountConnected;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageFailed;
use Modules\CRM\Models\Webhook;
use Modules\CRM\Jobs\CallWebhookJob;

class DispatchWhatsAppWebhooks
{
    protected array $eventMapping = [
        WhatsAppMessageReceived::class      => 'whatsapp.message.received',
        WhatsAppMessageSent::class          => 'whatsapp.message.sent',
        WhatsAppConversationAssigned::class  => 'whatsapp.conversation.assigned',
        WhatsAppConversationResolved::class  => 'whatsapp.conversation.resolved',
        WhatsAppAccountConnected::class      => 'whatsapp.account.connected',
        WhatsAppMessageFailed::class        => 'whatsapp.message.failed',
    ];

    public function handle(
        WhatsAppMessageReceived|WhatsAppMessageSent|WhatsAppConversationAssigned|WhatsAppConversationResolved|WhatsAppAccountConnected|WhatsAppMessageFailed $event
    ): void {
        $eventName = $this->eventMapping[get_class($event)] ?? 'whatsapp.unknown';

        $webhooks = Webhook::where('workspace_id', $event->workspaceId)
            ->where('is_active', true)
            ->where(function ($query) use ($eventName) {
                $query->whereJsonContains('events', $eventName)
                      ->orWhereJsonContains('events', 'whatsapp.*')
                      ->orWhereJsonContains('events', '*');
            })
            ->get();

        if ($webhooks->isEmpty()) {
            return;
        }

        $payload = [
            'event'        => $eventName,
            'timestamp'    => now()->toIso8601String(),
            'workspace_id' => $event->workspaceId,
            'data'         => $this->buildPayload($event),
        ];

        foreach ($webhooks as $webhook) {
            CallWebhookJob::dispatch($webhook, $payload);
        }
    }

    protected function buildPayload(object $event): array
    {
        $data = [];

        if (property_exists($event, 'message')) {
            $data['message'] = [
                'id'              => $event->message->id,
                'uuid'            => $event->message->uuid,
                'type'            => $event->message->type,
                'body'            => $event->message->body,
                'sender_type'     => $event->message->sender_type,
                'delivery_status' => $event->message->delivery_status,
                'created_at'      => $event->message->created_at->toIso8601String(),
            ];
        }

        if (property_exists($event, 'conversation')) {
            $data['conversation'] = [
                'id'            => $event->conversation->id,
                'uuid'          => $event->conversation->uuid,
                'contact_phone' => $event->conversation->contact_phone,
                'contact_name'  => $event->conversation->contact_name,
                'status'        => $event->conversation->status,
                'type'          => $event->conversation->type,
            ];
        }

        if (property_exists($event, 'account')) {
            $data['account'] = [
                'id'           => $event->account->id,
                'phone_number' => $event->account->phone_number,
                'status'       => $event->account->status,
            ];
        }

        if (property_exists($event, 'reason')) {
            $data['reason'] = $event->reason;
        }

        if (property_exists($event, 'agent')) {
            $data['agent'] = [
                'id'    => $event->agent->id,
                'name'  => $event->agent->name,
                'email' => $event->agent->email,
            ];
        }

        return $data;
    }
}
