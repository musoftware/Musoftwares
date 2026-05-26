<?php

namespace App\Modules\CRMWhatsAppInbox\Services;

use App\Modules\CRMWhatsAppInbox\Notifications\SlaBreachNotification;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppSlaPolicy;
use Illuminate\Support\Collection;

class WhatsAppSlaEngine
{
    /**
     * Apply the appropriate SLA policy to a conversation.
     */
    public function applySla(WhatsAppConversation $conversation): void
    {
        $policy = $this->resolvePolicy($conversation);

        if (!$policy) {
            return;
        }

        $conversation->update([
            'sla_policy_id' => $policy->id,
            'sla_due_at'    => $policy->calculateDueAt(now()),
            'sla_breached'  => false,
        ]);
    }

    /**
     * Record first response time and check SLA compliance.
     */
    public function recordFirstResponse(WhatsAppConversation $conversation): void
    {
        if ($conversation->first_response_at) {
            return; // Already recorded
        }

        $conversation->update([
            'first_response_at' => now(),
        ]);
    }

    /**
     * Record conversation resolution.
     */
    public function recordResolution(WhatsAppConversation $conversation): void
    {
        $conversation->update([
            'resolved_at' => now(),
            'status'      => 'resolved',
        ]);
    }

    /**
     * Check first response SLA compliance for a conversation.
     */
    public function checkFirstResponse(WhatsAppConversation $conversation): bool
    {
        if (!$conversation->sla_due_at) {
            return true; // No SLA configured
        }

        // If already responded, SLA was met
        if ($conversation->first_response_at) {
            return $conversation->first_response_at->lte($conversation->sla_due_at);
        }

        // Still waiting — check if overdue
        return now()->lte($conversation->sla_due_at);
    }

    /**
     * Check resolution SLA compliance for a conversation.
     */
    public function checkResolution(WhatsAppConversation $conversation): bool
    {
        $policy = $conversation->slaPolicy;
        if (!$policy) {
            return true;
        }

        $resolutionDue = $policy->calculateResolutionDueAt($conversation->created_at);

        if ($conversation->resolved_at) {
            return $conversation->resolved_at->lte($resolutionDue);
        }

        return now()->lte($resolutionDue);
    }

    /**
     * Get all conversations that have breached their SLA.
     */
    public function getOverdueConversations(int $workspaceId): Collection
    {
        return WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereIn('status', ['open', 'pending'])
            ->where(function ($query) {
                $query->where('sla_breached', true)
                      ->orWhere(function ($q) {
                          $q->whereNotNull('sla_due_at')
                            ->where('sla_due_at', '<=', now())
                            ->whereNull('first_response_at');
                      });
            })
            ->with(['assignedAgent:id,name,email', 'slaPolicy'])
            ->get();
    }

    /**
     * Check all open conversations for SLA breaches (called by scheduler).
     */
    public function checkAllBreaches(): int
    {
        $breachedCount = 0;

        $conversations = WhatsAppConversation::withoutGlobalScopes()
            ->whereIn('status', ['open', 'pending'])
            ->where('sla_breached', false)
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<=', now())
            ->whereNull('first_response_at')
            ->with(['assignedAgent', 'slaPolicy.escalationUser'])
            ->cursor();

        foreach ($conversations as $conversation) {
            $conversation->update(['sla_breached' => true]);
            $breachedCount++;

            // Send breach notification
            $this->notifyBreach($conversation);
        }

        return $breachedCount;
    }

    /**
     * Get SLA compliance statistics for a workspace.
     */
    public function getComplianceStats(int $workspaceId, ?string $period = null): array
    {
        $query = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->whereNotNull('sla_policy_id');

        if ($period) {
            $startDate = match ($period) {
                'today'      => now()->startOfDay(),
                'week'       => now()->startOfWeek(),
                'month'      => now()->startOfMonth(),
                'quarter'    => now()->startOfQuarter(),
                default      => now()->startOfMonth(),
            };
            $query->where('created_at', '>=', $startDate);
        }

        $total = (clone $query)->count();
        $breached = (clone $query)->where('sla_breached', true)->count();
        $responded = (clone $query)->whereNotNull('first_response_at')->count();
        $resolved = (clone $query)->whereNotNull('resolved_at')->count();

        // Average first response time (in minutes)
        $avgFirstResponse = (clone $query)
            ->whereNotNull('first_response_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_minutes')
            ->value('avg_minutes');

        // Average resolution time (in minutes)
        $avgResolution = (clone $query)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');

        return [
            'total_conversations'      => $total,
            'sla_breached'             => $breached,
            'compliance_rate'          => $total > 0 ? round((($total - $breached) / $total) * 100, 1) : 100,
            'responded'                => $responded,
            'resolved'                 => $resolved,
            'avg_first_response_min'   => round($avgFirstResponse ?? 0, 1),
            'avg_resolution_min'       => round($avgResolution ?? 0, 1),
        ];
    }

    /**
     * Resolve the best-matching SLA policy for a conversation.
     */
    protected function resolvePolicy(WhatsAppConversation $conversation): ?WhatsAppSlaPolicy
    {
        // Try to find a policy matching the conversation's priority
        $policy = WhatsAppSlaPolicy::withoutGlobalScopes()
            ->where('workspace_id', $conversation->workspace_id)
            ->where('is_active', true)
            ->where('priority', $conversation->priority)
            ->first();

        // Fall back to default policy
        if (!$policy) {
            $policy = WhatsAppSlaPolicy::withoutGlobalScopes()
                ->where('workspace_id', $conversation->workspace_id)
                ->where('is_active', true)
                ->where('is_default', true)
                ->first();
        }

        return $policy;
    }

    /**
     * Send SLA breach notification.
     */
    protected function notifyBreach(WhatsAppConversation $conversation): void
    {
        $policy = $conversation->slaPolicy;

        // Notify assigned agent
        if ($conversation->assignedAgent) {
            $conversation->assignedAgent->notify(new SlaBreachNotification($conversation));
        }

        // Notify escalation user
        if ($policy && $policy->notify_on_breach && $policy->escalationUser) {
            $policy->escalationUser->notify(new SlaBreachNotification($conversation));
        }
    }
}
