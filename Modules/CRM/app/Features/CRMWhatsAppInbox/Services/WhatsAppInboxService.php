<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Services;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Modules\CRM\Models\Lead;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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
