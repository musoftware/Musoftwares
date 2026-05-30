<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Services;

use Modules\CRM\Models\WhatsAppConversation;

class ConversationRoutingEngine
{
    public function __construct(
        protected ConversationAssignmentEngine $assignmentEngine,
    ) {}

    /**
     * Route a new conversation based on configured rules.
     */
    public function routeNewConversation(WhatsAppConversation $conversation): void
    {
        // 1. Check if VIP routing applies
        if ($this->isVipContact($conversation)) {
            $conversation->update(['priority' => 'urgent']);
            $this->assignmentEngine->assign($conversation, 'vip');
            return;
        }

        // 2. Check if department routing applies
        if ($conversation->assigned_department) {
            $this->assignmentEngine->assign($conversation, 'department');
            return;
        }

        // 3. Infer type from context and route accordingly
        $type = $this->inferConversationType($conversation);
        if ($type !== $conversation->type) {
            $conversation->update(['type' => $type]);
        }

        // 4. Apply default assignment strategy
        $this->assignmentEngine->assign($conversation);
    }

    /**
     * Re-route a conversation when its type or priority changes.
     */
    public function reroute(WhatsAppConversation $conversation): void
    {
        // Only reroute if unassigned or if routing rules dictate
        if (!$conversation->isAssigned()) {
            $this->routeNewConversation($conversation);
        }
    }

    /**
     * Check if a contact should be treated as VIP.
     */
    protected function isVipContact(WhatsAppConversation $conversation): bool
    {
        // Check if the linked lead has high value or VIP tag
        if ($conversation->lead_id) {
            $lead = $conversation->lead;
            if ($lead) {
                // Check for VIP-related tags
                $vipTags = $lead->tags()->whereIn('name', ['VIP', 'vip', 'High Value', 'Priority'])->exists();
                if ($vipTags) {
                    return true;
                }
            }
        }

        // Check conversation metadata for VIP flag
        return ($conversation->metadata['is_vip'] ?? false);
    }

    /**
     * Infer the conversation type from available context.
     */
    protected function inferConversationType(WhatsAppConversation $conversation): string
    {
        // If linked to a lead, it's a lead conversation
        if ($conversation->lead_id) {
            $lead = $conversation->lead;
            if ($lead) {
                return match ($lead->status) {
                    'new', 'contacted', 'qualified' => 'lead',
                    'negotiation', 'proposal'       => 'sales',
                    default                          => 'general',
                };
            }
            return 'lead';
        }

        return $conversation->type ?? 'general';
    }

    /**
     * Get routing statistics for a workspace.
     */
    public function getRoutingStats(int $workspaceId): array
    {
        $conversations = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['open', 'pending']);

        return [
            'total_open'     => (clone $conversations)->count(),
            'unassigned'     => (clone $conversations)->whereNull('assigned_agent_id')->count(),
            'by_type'        => (clone $conversations)->selectRaw('type, COUNT(*) as count')->groupBy('type')->pluck('count', 'type'),
            'by_priority'    => (clone $conversations)->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->pluck('count', 'priority'),
            'sla_breached'   => (clone $conversations)->where('sla_breached', true)->count(),
        ];
    }
}
