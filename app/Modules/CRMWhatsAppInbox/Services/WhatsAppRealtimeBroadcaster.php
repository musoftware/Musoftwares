<?php

namespace App\Modules\CRMWhatsAppInbox\Services;

use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Broadcast;

class WhatsAppRealtimeBroadcaster
{
    /**
     * Broadcast a new incoming/outgoing message.
     */
    public function broadcastNewMessage(WhatsAppMessage $message, WhatsAppConversation $conversation): void
    {
        $payload = [
            'message'      => $this->formatMessage($message),
            'conversation' => $this->formatConversationPreview($conversation),
        ];

        // Broadcast to the specific conversation channel
        $this->broadcastToChannel(
            "crm.workspace.{$conversation->workspace_id}.conversation.{$conversation->id}",
            'NewMessage',
            $payload
        );

        // Broadcast to the workspace inbox channel (for conversation list updates)
        $this->broadcastToChannel(
            "crm.workspace.{$conversation->workspace_id}.inbox",
            'ConversationUpdated',
            ['conversation' => $this->formatConversationPreview($conversation)]
        );
    }

    /**
     * Broadcast a typing indicator.
     */
    public function broadcastTyping(int $workspaceId, int $conversationId, int $userId, bool $isTyping): void
    {
        $this->broadcastToChannel(
            "crm.workspace.{$workspaceId}.conversation.{$conversationId}",
            'TypingIndicator',
            [
                'user_id'   => $userId,
                'is_typing' => $isTyping,
            ]
        );
    }

    /**
     * Broadcast message delivery status update.
     */
    public function broadcastMessageStatus(WhatsAppMessage $message): void
    {
        $this->broadcastToChannel(
            "crm.workspace.{$message->workspace_id}.conversation.{$message->conversation_id}",
            'MessageStatusUpdated',
            [
                'message_id'      => $message->id,
                'delivery_status' => $message->delivery_status,
                'sent_at'         => $message->sent_at?->toIso8601String(),
                'delivered_at'    => $message->delivered_at?->toIso8601String(),
                'read_at'         => $message->read_at?->toIso8601String(),
            ]
        );
    }

    /**
     * Broadcast conversation assignment change.
     */
    public function broadcastAssignment(WhatsAppConversation $conversation, ?\App\Models\User $agent): void
    {
        $payload = [
            'conversation' => $this->formatConversationPreview($conversation),
            'agent'        => $agent ? [
                'id'     => $agent->id,
                'name'   => $agent->name,
                'avatar' => $agent->profile_photo_path,
            ] : null,
        ];

        $this->broadcastToChannel(
            "crm.workspace.{$conversation->workspace_id}.inbox",
            'ConversationAssigned',
            $payload
        );

        $this->broadcastToChannel(
            "crm.workspace.{$conversation->workspace_id}.agents",
            'AssignmentChanged',
            $payload
        );
    }

    /**
     * Broadcast agent online/offline status.
     */
    public function broadcastAgentStatus(int $workspaceId, int $userId, string $status): void
    {
        $this->broadcastToChannel(
            "crm.workspace.{$workspaceId}.agents",
            'AgentStatus',
            [
                'user_id' => $userId,
                'status'  => $status, // online, offline, away
            ]
        );
    }

    /**
     * Broadcast conversation update (status change, label change, etc.).
     */
    public function broadcastConversationUpdate(WhatsAppConversation $conversation): void
    {
        $this->broadcastToChannel(
            "crm.workspace.{$conversation->workspace_id}.inbox",
            'ConversationUpdated',
            ['conversation' => $this->formatConversationPreview($conversation)]
        );
    }

    /**
     * Dispatch a broadcast on a private channel.
     */
    protected function broadcastToChannel(string $channel, string $eventName, array $data): void
    {
        try {
            broadcast(new \App\Modules\CRMWhatsAppInbox\Events\GenericBroadcastEvent(
                $channel,
                $eventName,
                $data
            ))->toOthers();
        } catch (\Exception $e) {
            // Log but don't fail — broadcasting is best-effort
            \Log::warning("WhatsApp broadcast failed: {$e->getMessage()}", [
                'channel' => $channel,
                'event'   => $eventName,
            ]);
        }
    }

    /**
     * Format a message for broadcast payload.
     */
    protected function formatMessage(WhatsAppMessage $message): array
    {
        return [
            'id'              => $message->id,
            'uuid'            => $message->uuid,
            'sender_type'     => $message->sender_type,
            'sender_id'       => $message->sender_id,
            'sender_name'     => $message->sender?->name,
            'type'            => $message->type,
            'body'            => $message->body,
            'media_url'       => $message->media_url,
            'media_mime_type' => $message->media_mime_type,
            'media_filename'  => $message->media_filename,
            'is_internal_note' => $message->is_internal_note,
            'delivery_status' => $message->delivery_status,
            'created_at'      => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Format a conversation preview for broadcast payload.
     */
    protected function formatConversationPreview(WhatsAppConversation $conversation): array
    {
        return [
            'id'                   => $conversation->id,
            'uuid'                 => $conversation->uuid,
            'contact_name'         => $conversation->contact_name,
            'contact_phone'        => $conversation->contact_phone,
            'contact_avatar'       => $conversation->contact_avatar,
            'status'               => $conversation->status,
            'priority'             => $conversation->priority,
            'type'                 => $conversation->type,
            'assigned_agent_id'    => $conversation->assigned_agent_id,
            'unread_count'         => $conversation->unread_count,
            'is_pinned'            => $conversation->is_pinned,
            'last_message_at'      => $conversation->last_message_at?->toIso8601String(),
            'last_message_preview' => $conversation->last_message_preview,
        ];
    }
}
