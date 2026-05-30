<?php

namespace Modules\CRM\Domains\Communication\Actions;

use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppMessage;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\CRMWhatsAppLimitsService;
use Modules\CRM\Domains\Communication\DTOs\IncomingWhatsAppMessageDTO;
use Modules\CRM\Models\Lead;

class ProcessIncomingWhatsAppMessageAction
{
    public function __construct(
        protected ResolveWhatsAppConversationAction $resolveConversationAction,
        protected CRMWhatsAppLimitsService $limitsService,
    ) {}

    public function execute(WhatsAppAccount $account, array $rawPayload): WhatsAppMessage
    {
        $dto = IncomingWhatsAppMessageDTO::fromWebhookPayload($rawPayload);
        $workspaceId = $account->workspace_id;

        // 1. Resolve or create conversation
        $conversation = $this->resolveConversationAction->execute($account, $dto->contactPhone, $dto->contactName);

        // 2. Create the message record
        $message = WhatsAppMessage::create([
            'workspace_id'        => $workspaceId,
            'conversation_id'     => $conversation->id,
            'sender_type'         => 'customer',
            'whatsapp_message_id' => $dto->messageId,
            'type'                => $dto->type,
            'body'                => $dto->body,
            'media_url'           => $dto->mediaUrl,
            'media_mime_type'     => $dto->mediaMimeType,
            'media_size'          => $dto->mediaSize,
            'media_filename'      => $dto->mediaFilename,
            'delivery_status'     => 'delivered',
            'sent_at'             => now(),
            'delivered_at'        => now(),
            'metadata'            => $dto->metadata,
        ]);

        // 3. Update conversation denormalized fields atomically
        WhatsAppConversation::withoutGlobalScopes()
            ->where('id', $conversation->id)
            ->increment('unread_count', 1, [
                'last_message_at'      => now(),
                'last_message_preview' => $message->getPreview(),
                'status'               => $conversation->status === 'resolved' ? 'open' : $conversation->status,
            ]);

        // 4. Match to lead/customer
        $this->matchLeadOrCustomer($conversation, $dto->contactPhone);

        // 5. Track usage
        $this->limitsService->increaseUsage($workspaceId, 'monthly_whatsapp_messages');

        // 6. Fire event (listeners handle broadcast, timeline, automations, webhooks)
        event(new WhatsAppMessageReceived($workspaceId, $message, $conversation));

        return $message;
    }

    protected function matchLeadOrCustomer(WhatsAppConversation $conversation, string $phone): void
    {
        if ($conversation->lead_id) {
            return;
        }

        $normalizedPhone = preg_replace('/[\s\-\+]/', '', $phone);

        $lead = Lead::withoutGlobalScopes()
            ->where('workspace_id', $conversation->workspace_id)
            ->where(function ($query) use ($normalizedPhone, $phone) {
                $query->where('phone', $phone)
                      ->orWhere('phone', $normalizedPhone)
                      ->orWhere('phone', '+' . $normalizedPhone);
            })
            ->first();

        if ($lead) {
            $conversation->update([
                'lead_id' => $lead->id,
                'type'    => 'lead',
            ]);
        }
    }
}
