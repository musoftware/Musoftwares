<?php

namespace App\Modules\CRMWhatsAppInbox\Services;

use App\Modules\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Modules\CRM\Models\Lead;
use Illuminate\Support\Str;

class WhatsAppInboxService
{
    public function __construct(
        protected WhatsAppProviderInterface $provider,
        protected ConversationRoutingEngine $routingEngine,
        protected ConversationAssignmentEngine $assignmentEngine,
        protected WhatsAppRealtimeBroadcaster $broadcaster,
        protected CRMWhatsAppLimitsService $limitsService,
        protected WhatsAppSlaEngine $slaEngine,
    ) {}

    /**
     * Process an incoming WhatsApp message from the webhook.
     */
    public function processIncomingMessage(WhatsAppAccount $account, array $payload): WhatsAppMessage
    {
        $workspaceId = $account->workspace_id;
        $contactPhone = $payload['from'] ?? $payload['contact_phone'];
        $contactName = $payload['push_name'] ?? $payload['contact_name'] ?? null;

        // 1. Resolve or create conversation
        $conversation = $this->resolveConversation($account, $contactPhone, $contactName);

        // 2. Create the message record
        $message = WhatsAppMessage::create([
            'workspace_id'        => $workspaceId,
            'conversation_id'     => $conversation->id,
            'sender_type'         => 'customer',
            'whatsapp_message_id' => $payload['message_id'] ?? null,
            'type'                => $payload['type'] ?? 'text',
            'body'                => $payload['body'] ?? null,
            'media_url'           => $payload['media_url'] ?? null,
            'media_mime_type'     => $payload['media_mime_type'] ?? null,
            'media_size'          => $payload['media_size'] ?? null,
            'media_filename'      => $payload['media_filename'] ?? null,
            'delivery_status'     => 'delivered',
            'sent_at'             => now(),
            'delivered_at'        => now(),
            'metadata'            => $payload['metadata'] ?? null,
        ]);

        // 3. Update conversation denormalized fields
        $conversation->update([
            'last_message_at'      => now(),
            'last_message_preview' => $message->getPreview(),
            'unread_count'         => $conversation->unread_count + 1,
            'status'               => $conversation->status === 'resolved' ? 'open' : $conversation->status,
        ]);

        // 4. Match to lead/customer
        $this->matchLeadOrCustomer($conversation, $contactPhone);

        // 5. Track usage
        $this->limitsService->increaseUsage($workspaceId, 'monthly_whatsapp_messages');

        // 6. Fire event (listeners handle broadcast, timeline, automations, webhooks)
        event(new WhatsAppMessageReceived($workspaceId, $message, $conversation));

        return $message;
    }

    /**
     * Resolve existing conversation or create a new one for the contact.
     */
    public function resolveConversation(WhatsAppAccount $account, string $contactPhone, ?string $contactName = null): WhatsAppConversation
    {
        $conversation = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $account->workspace_id)
            ->where('account_id', $account->id)
            ->where('contact_phone', $contactPhone)
            ->whereIn('status', ['open', 'pending'])
            ->first();

        if ($conversation) {
            // Update contact name if we now have one
            if ($contactName && !$conversation->contact_name) {
                $conversation->update(['contact_name' => $contactName]);
            }
            return $conversation;
        }

        // Create new conversation
        $conversation = WhatsAppConversation::create([
            'uuid'          => (string) Str::uuid(),
            'workspace_id'  => $account->workspace_id,
            'account_id'    => $account->id,
            'contact_phone' => $contactPhone,
            'contact_name'  => $contactName,
            'type'          => 'general',
            'status'        => 'open',
            'priority'      => 'medium',
        ]);

        // Apply SLA policy
        $this->slaEngine->applySla($conversation);

        // Attempt auto-assignment via routing engine
        $this->routingEngine->routeNewConversation($conversation);

        return $conversation;
    }

    /**
     * Match incoming contact phone to existing CRM lead or customer.
     */
    public function matchLeadOrCustomer(WhatsAppConversation $conversation, string $phone): void
    {
        if ($conversation->lead_id) {
            return; // Already linked
        }

        // Normalize phone number (strip +, spaces, dashes)
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

    /**
     * Get inbox conversations with filters for the current workspace.
     */
    public function getInbox(int $workspaceId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['assignedAgent:id,name,email,profile_photo_path', 'labels', 'account:id,name,phone_number'])
            ->latest('last_message_at');

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['assigned_agent_id'])) {
            $query->where('assigned_agent_id', $filters['assigned_agent_id']);
        }

        if (isset($filters['unassigned']) && $filters['unassigned']) {
            $query->whereNull('assigned_agent_id');
        }

        if (!empty($filters['label_id'])) {
            $query->whereHas('labels', fn($q) => $q->where('crm_whatsapp_labels.id', $filters['label_id']));
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('contact_name', 'like', "%{$term}%")
                  ->orWhere('contact_phone', 'like', "%{$term}%")
                  ->orWhere('last_message_preview', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['sla_breached']) && $filters['sla_breached']) {
            $query->where('sla_breached', true);
        }

        if (isset($filters['is_pinned']) && $filters['is_pinned']) {
            $query->where('is_pinned', true);
        }

        return $query->paginate($filters['per_page'] ?? 25);
    }

    /**
     * Search messages across conversations.
     */
    public function searchMessages(int $workspaceId, string $query, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $search = WhatsAppMessage::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->with(['conversation:id,uuid,contact_name,contact_phone', 'sender:id,name'])
            ->whereRaw('MATCH(body) AGAINST(? IN BOOLEAN MODE)', [$query]);

        if (!empty($filters['conversation_id'])) {
            $search->where('conversation_id', $filters['conversation_id']);
        }

        if (!empty($filters['type'])) {
            $search->where('type', $filters['type']);
        }

        if (!empty($filters['sender_type'])) {
            $search->where('sender_type', $filters['sender_type']);
        }

        if (!empty($filters['date_from'])) {
            $search->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $search->where('created_at', '<=', $filters['date_to']);
        }

        return $search->latest()->paginate($filters['per_page'] ?? 20);
    }
}
