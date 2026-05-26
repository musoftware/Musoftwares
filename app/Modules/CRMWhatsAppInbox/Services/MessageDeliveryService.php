<?php

namespace App\Modules\CRMWhatsAppInbox\Services;

use App\Modules\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageFailed;
use App\Modules\CRMWhatsAppInbox\Jobs\SendWhatsAppMessageJob;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;

class MessageDeliveryService
{
    public function __construct(
        protected CRMWhatsAppLimitsService $limitsService,
    ) {}

    /**
     * Compose and queue an outgoing text message.
     */
    public function sendText(
        WhatsAppConversation $conversation,
        string $body,
        int $senderId,
        ?int $quotedMessageId = null,
        ?\Carbon\Carbon $scheduledAt = null
    ): WhatsAppMessage {
        return $this->createAndDispatch($conversation, [
            'type'              => 'text',
            'body'              => $body,
            'sender_id'         => $senderId,
            'quoted_message_id' => $quotedMessageId,
            'scheduled_at'      => $scheduledAt,
        ]);
    }

    /**
     * Compose and queue an outgoing media message.
     */
    public function sendMedia(
        WhatsAppConversation $conversation,
        string $mediaUrl,
        string $type,
        int $senderId,
        ?string $caption = null,
        ?string $mimeType = null,
        ?string $filename = null,
        ?int $size = null
    ): WhatsAppMessage {
        return $this->createAndDispatch($conversation, [
            'type'           => $type,
            'body'           => $caption,
            'media_url'      => $mediaUrl,
            'media_mime_type' => $mimeType,
            'media_filename' => $filename,
            'media_size'     => $size,
            'sender_id'      => $senderId,
        ]);
    }

    /**
     * Compose and queue a template message.
     */
    public function sendTemplate(
        WhatsAppConversation $conversation,
        string $templateName,
        array $params,
        int $senderId
    ): WhatsAppMessage {
        return $this->createAndDispatch($conversation, [
            'type'            => 'template',
            'template_name'   => $templateName,
            'template_params' => $params,
            'sender_id'       => $senderId,
        ]);
    }

    /**
     * Add an internal note to a conversation (not sent via WhatsApp).
     */
    public function addInternalNote(
        WhatsAppConversation $conversation,
        string $body,
        int $senderId,
        ?array $mentions = null
    ): WhatsAppMessage {
        $message = WhatsAppMessage::create([
            'workspace_id'    => $conversation->workspace_id,
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agent',
            'sender_id'       => $senderId,
            'type'            => 'text',
            'body'            => $body,
            'is_internal_note' => true,
            'mentions'        => $mentions,
            'delivery_status' => 'delivered',
            'sent_at'         => now(),
            'delivered_at'    => now(),
        ]);

        // Update conversation timestamp
        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => '📝 Internal note',
        ]);

        return $message;
    }

    /**
     * Send a reaction to a message.
     */
    public function react(
        WhatsAppMessage $targetMessage,
        string $emoji,
        int $senderId
    ): WhatsAppMessage {
        return $this->createAndDispatch($targetMessage->conversation, [
            'type'              => 'reaction',
            'reaction_emoji'    => $emoji,
            'quoted_message_id' => $targetMessage->id,
            'sender_id'        => $senderId,
        ]);
    }

    /**
     * Create the message record and dispatch the send job.
     */
    protected function createAndDispatch(WhatsAppConversation $conversation, array $data): WhatsAppMessage
    {
        $workspaceId = $conversation->workspace_id;

        // Check usage limits
        if (!$this->limitsService->canUse($workspaceId, 'monthly_whatsapp_messages')) {
            throw new \App\Modules\CRMWhatsAppInbox\Exceptions\UsageLimitExceededException(
                'Monthly WhatsApp message limit reached.'
            );
        }

        $message = WhatsAppMessage::create(array_merge([
            'workspace_id'    => $workspaceId,
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agent',
            'delivery_status' => $data['scheduled_at'] ? 'pending' : 'pending',
        ], $data));

        // Don't dispatch if scheduled for later
        if (!empty($data['scheduled_at'])) {
            SendWhatsAppMessageJob::dispatch($message)
                ->delay($data['scheduled_at'])
                ->onQueue('whatsapp-outgoing');
        } else {
            SendWhatsAppMessageJob::dispatch($message)
                ->onQueue('whatsapp-outgoing');
        }

        // Update conversation
        if (!$message->is_internal_note) {
            $conversation->update([
                'last_message_at'      => now(),
                'last_message_preview' => $message->getPreview(),
            ]);
        }

        return $message;
    }

    /**
     * Mark a message as sent (called by the SendWhatsAppMessageJob after provider confirms).
     */
    public function markAsSent(WhatsAppMessage $message, string $whatsappMessageId): void
    {
        $message->update([
            'whatsapp_message_id' => $whatsappMessageId,
            'delivery_status'     => 'sent',
            'sent_at'             => now(),
        ]);

        $this->limitsService->increaseUsage($message->workspace_id, 'monthly_whatsapp_messages');

        event(new WhatsAppMessageSent($message->workspace_id, $message, $message->conversation));
    }

    /**
     * Mark a message as failed (called by the SendWhatsAppMessageJob on failure).
     */
    public function markAsFailed(WhatsAppMessage $message, string $reason): void
    {
        $message->update([
            'delivery_status' => 'failed',
            'failed_reason'   => $reason,
        ]);

        event(new WhatsAppMessageFailed($message->workspace_id, $message, $reason));
    }

    /**
     * Update delivery status from provider callback.
     */
    public function updateDeliveryStatus(string $whatsappMessageId, string $status): void
    {
        $message = WhatsAppMessage::where('whatsapp_message_id', $whatsappMessageId)->first();

        if (!$message) {
            return;
        }

        $updates = ['delivery_status' => $status];

        match ($status) {
            'delivered' => $updates['delivered_at'] = now(),
            'read'      => $updates['read_at'] = now(),
            default     => null,
        };

        $message->update($updates);
    }
}
