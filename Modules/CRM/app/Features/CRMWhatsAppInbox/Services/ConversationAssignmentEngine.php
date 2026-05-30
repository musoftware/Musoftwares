<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Services;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppAssignment;
use Modules\CRM\Models\WhatsAppParticipant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConversationAssignmentEngine
{
    /**
     * Assign a conversation to an agent using the specified strategy.
     */
    public function assign(
        WhatsAppConversation $conversation,
        ?string $strategy = null,
        ?User $assignedBy = null,
        ?string $reason = null
    ): ?User {
        $strategy = $strategy ?? $this->getDefaultStrategy($conversation->workspace_id);

        $agent = match ($strategy) {
            'manual'      => null, // Manual doesn't auto-assign
            'round_robin' => $this->roundRobin($conversation->workspace_id),
            'workload'    => $this->workloadBalance($conversation->workspace_id),
            'department'  => $this->departmentRoute($conversation),
            'vip'         => $this->vipRoute($conversation),
            default       => null,
        };

        if ($agent) {
            $this->performAssignment($conversation, $agent, $assignedBy, $strategy, $reason);
        }

        return $agent;
    }

    /**
     * Manually assign a conversation to a specific agent.
     */
    public function manualAssign(
        WhatsAppConversation $conversation,
        User $agent,
        ?User $assignedBy = null,
        ?string $reason = null
    ): void {
        $this->performAssignment($conversation, $agent, $assignedBy, 'manual', $reason);
    }

    /**
     * Reassign a conversation from one agent to another.
     */
    public function reassign(
        WhatsAppConversation $conversation,
        User $newAgent,
        ?User $assignedBy = null,
        ?string $reason = null
    ): void {
        $this->performAssignment($conversation, $newAgent, $assignedBy, 'manual', $reason ?? 'Reassigned');
    }

    /**
     * Transfer a conversation to a department.
     */
    public function transfer(
        WhatsAppConversation $conversation,
        string $department,
        ?User $transferredBy = null
    ): void {
        $previousAgent = $conversation->assigned_agent_id
            ? User::find($conversation->assigned_agent_id)
            : null;

        DB::transaction(function () use ($conversation, $department, $previousAgent, $transferredBy) {
            // Update department
            $conversation->update([
                'assigned_department' => $department,
                'assigned_agent_id'   => null,
            ]);

            // Record the transfer
            WhatsAppAssignment::create([
                'workspace_id'    => $conversation->workspace_id,
                'conversation_id' => $conversation->id,
                'assigned_from_id' => $previousAgent?->id,
                'assigned_to_id'   => null,
                'assigned_by_id'   => $transferredBy?->id,
                'assignment_type'  => 'department',
                'reason'           => "Transferred to {$department}",
            ]);

            // Remove previous participant if they left
            if ($previousAgent) {
                WhatsAppParticipant::where('conversation_id', $conversation->id)
                    ->where('user_id', $previousAgent->id)
                    ->whereNull('left_at')
                    ->update(['left_at' => now()]);
            }
        });

        // Try auto-assignment within the new department
        $this->assign($conversation, 'department');
    }

    /**
     * Perform the actual assignment operation.
     */
    protected function performAssignment(
        WhatsAppConversation $conversation,
        User $agent,
        ?User $assignedBy,
        string $type,
        ?string $reason
    ): void {
        $previousAgentId = $conversation->assigned_agent_id;

        DB::transaction(function () use ($conversation, $agent, $assignedBy, $type, $reason, $previousAgentId) {
            // Update conversation
            $conversation->update([
                'assigned_agent_id' => $agent->id,
                'status'            => $conversation->status === 'open' ? 'pending' : $conversation->status,
            ]);

            // Create assignment record
            WhatsAppAssignment::create([
                'workspace_id'     => $conversation->workspace_id,
                'conversation_id'  => $conversation->id,
                'assigned_from_id' => $previousAgentId,
                'assigned_to_id'   => $agent->id,
                'assigned_by_id'   => $assignedBy?->id,
                'assignment_type'  => $type,
                'reason'           => $reason,
            ]);

            // Add agent as participant
            WhatsAppParticipant::updateOrCreate(
                [
                    'conversation_id' => $conversation->id,
                    'user_id'         => $agent->id,
                ],
                [
                    'workspace_id' => $conversation->workspace_id,
                    'role'         => 'participant',
                    'joined_at'    => now(),
                    'left_at'      => null,
                ]
            );

            // Mark previous agent as left (if different)
            if ($previousAgentId && $previousAgentId !== $agent->id) {
                WhatsAppParticipant::where('conversation_id', $conversation->id)
                    ->where('user_id', $previousAgentId)
                    ->whereNull('left_at')
                    ->update(['left_at' => now()]);
            }
        });

        event(new WhatsAppConversationAssigned(
            $conversation->workspace_id,
            $conversation,
            $agent,
            $assignedBy,
            $type
        ));
    }

    /**
     * Round-robin: assign to the agent who was assigned least recently.
     */
    protected function roundRobin(int $workspaceId): ?User
    {
        $agentIds = $this->getAvailableAgentIds($workspaceId);

        if ($agentIds->isEmpty()) {
            return null;
        }

        // Get the agent who was assigned least recently
        $lastAssigned = WhatsAppAssignment::where('workspace_id', $workspaceId)
            ->whereIn('assigned_to_id', $agentIds)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastAssigned) {
            return User::find($agentIds->first());
        }

        // Find the next agent in the rotation
        $currentIndex = $agentIds->search($lastAssigned->assigned_to_id);
        $nextIndex = ($currentIndex + 1) % $agentIds->count();

        return User::find($agentIds->get($nextIndex));
    }

    /**
     * Workload balance: assign to the agent with fewest active conversations.
     */
    protected function workloadBalance(int $workspaceId): ?User
    {
        $agentIds = $this->getAvailableAgentIds($workspaceId);

        if ($agentIds->isEmpty()) {
            return null;
        }

        $agentWithLeastConversations = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('assigned_agent_id', $agentIds)
            ->whereIn('status', ['open', 'pending'])
            ->select('assigned_agent_id', DB::raw('COUNT(*) as conversation_count'))
            ->groupBy('assigned_agent_id')
            ->orderBy('conversation_count', 'asc')
            ->first();

        if ($agentWithLeastConversations) {
            return User::find($agentWithLeastConversations->assigned_agent_id);
        }

        // All agents have 0 conversations, pick first
        return User::find($agentIds->first());
    }

    /**
     * Department route: find an agent from the matching department.
     */
    protected function departmentRoute(WhatsAppConversation $conversation): ?User
    {
        // Department mapping based on conversation type
        $department = $conversation->assigned_department ?? match ($conversation->type) {
            'support' => 'support',
            'sales'   => 'sales',
            'lead'    => 'sales',
            default   => null,
        };

        if (!$department) {
            return $this->workloadBalance($conversation->workspace_id);
        }

        // For now, delegate to workload balancing within the workspace
        // In a full implementation, this would filter agents by department
        return $this->workloadBalance($conversation->workspace_id);
    }

    /**
     * VIP route: assign to designated senior agents for high-value contacts.
     */
    protected function vipRoute(WhatsAppConversation $conversation): ?User
    {
        // VIP routing logic — for now falls back to workload balance
        // In production, this would check lead score, customer value, etc.
        return $this->workloadBalance($conversation->workspace_id);
    }

    /**
     * Get IDs of agents available for assignment in a workspace.
     */
    protected function getAvailableAgentIds(int $workspaceId): \Illuminate\Support\Collection
    {
        return DB::table('crm_workspace_users')
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->pluck('user_id');
    }

    /**
     * Get the default assignment strategy for a workspace.
     */
    protected function getDefaultStrategy(int $workspaceId): string
    {
        $workspace = \Modules\CRM\Models\Workspace::find($workspaceId);
        return $workspace?->settings['whatsapp_assignment_strategy'] ?? 'round_robin';
    }
}
