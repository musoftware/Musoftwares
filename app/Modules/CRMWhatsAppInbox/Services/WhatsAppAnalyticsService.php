<?php

namespace App\Modules\CRMWhatsAppInbox\Services;

use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Modules\CRM\Models\WhatsAppAssignment;
use Illuminate\Support\Facades\DB;

class WhatsAppAnalyticsService
{
    /**
     * Get overview analytics for a workspace.
     */
    public function getOverview(int $workspaceId, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        return [
            'message_volume'       => $this->getMessageVolume($workspaceId, $startDate),
            'conversation_stats'   => $this->getConversationStats($workspaceId, $startDate),
            'response_times'       => $this->getResponseTimes($workspaceId, $startDate),
            'resolution_stats'     => $this->getResolutionStats($workspaceId, $startDate),
            'automation_stats'     => $this->getAutomationStats($workspaceId, $startDate),
        ];
    }

    /**
     * Get per-agent performance metrics.
     */
    public function getAgentPerformance(int $workspaceId, string $period = 'month'): array
    {
        $startDate = $this->getStartDate($period);

        $agents = DB::table('crm_workspace_users')
            ->where('workspace_id', $workspaceId)
            ->where('is_active', true)
            ->join('users', 'users.id', '=', 'crm_workspace_users.user_id')
            ->select('users.id', 'users.name', 'users.email', 'users.profile_photo_path')
            ->get();

        return $agents->map(function ($agent) use ($workspaceId, $startDate) {
            $conversations = WhatsAppConversation::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('assigned_agent_id', $agent->id)
                ->where('created_at', '>=', $startDate);

            $messagesSent = WhatsAppMessage::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->where('sender_id', $agent->id)
                ->where('sender_type', 'agent')
                ->where('created_at', '>=', $startDate)
                ->count();

            $avgResponse = (clone $conversations)
                ->whereNotNull('first_response_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_min')
                ->value('avg_min');

            return [
                'agent' => [
                    'id'     => $agent->id,
                    'name'   => $agent->name,
                    'email'  => $agent->email,
                    'avatar' => $agent->profile_photo_path,
                ],
                'conversations_handled' => (clone $conversations)->count(),
                'conversations_resolved' => (clone $conversations)->where('status', 'resolved')->count(),
                'messages_sent'          => $messagesSent,
                'avg_response_min'       => round($avgResponse ?? 0, 1),
                'active_conversations'   => WhatsAppConversation::withoutGlobalScopes()
                    ->where('workspace_id', $workspaceId)
                    ->where('assigned_agent_id', $agent->id)
                    ->whereIn('status', ['open', 'pending'])
                    ->count(),
            ];
        })->toArray();
    }

    protected function getMessageVolume(int $workspaceId, \Carbon\Carbon $startDate): array
    {
        $incoming = WhatsAppMessage::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('sender_type', 'customer')
            ->where('created_at', '>=', $startDate)
            ->count();

        $outgoing = WhatsAppMessage::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('sender_type', 'agent')
            ->where('is_internal_note', false)
            ->where('created_at', '>=', $startDate)
            ->count();

        $failed = WhatsAppMessage::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('delivery_status', 'failed')
            ->where('created_at', '>=', $startDate)
            ->count();

        // Daily breakdown
        $daily = WhatsAppMessage::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, sender_type, COUNT(*) as count')
            ->groupBy('date', 'sender_type')
            ->get()
            ->groupBy('date')
            ->map(fn($group) => [
                'incoming' => $group->where('sender_type', 'customer')->sum('count'),
                'outgoing' => $group->where('sender_type', 'agent')->sum('count'),
            ]);

        return [
            'total_incoming' => $incoming,
            'total_outgoing' => $outgoing,
            'total_failed'   => $failed,
            'daily'          => $daily,
        ];
    }

    protected function getConversationStats(int $workspaceId, \Carbon\Carbon $startDate): array
    {
        $query = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', $startDate);

        return [
            'total_created'  => (clone $query)->count(),
            'currently_open' => WhatsAppConversation::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereIn('status', ['open', 'pending'])->count(),
            'resolved'       => (clone $query)->where('status', 'resolved')->count(),
            'by_type'        => (clone $query)->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')->pluck('count', 'type'),
            'unassigned'     => WhatsAppConversation::withoutGlobalScopes()
                ->where('workspace_id', $workspaceId)
                ->whereIn('status', ['open', 'pending'])
                ->whereNull('assigned_agent_id')->count(),
        ];
    }

    protected function getResponseTimes(int $workspaceId, \Carbon\Carbon $startDate): array
    {
        $query = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('first_response_at');

        $avg = (clone $query)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, first_response_at)) as avg_min')
            ->value('avg_min');

        $median = null; // Complex to compute in MySQL, skip for now

        return [
            'avg_first_response_min' => round($avg ?? 0, 1),
            'conversations_measured' => (clone $query)->count(),
        ];
    }

    protected function getResolutionStats(int $workspaceId, \Carbon\Carbon $startDate): array
    {
        $resolved = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId)
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('resolved_at');

        $avgResolution = (clone $resolved)
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_min')
            ->value('avg_min');

        return [
            'total_resolved'       => (clone $resolved)->count(),
            'avg_resolution_min'   => round($avgResolution ?? 0, 1),
        ];
    }

    protected function getAutomationStats(int $workspaceId, \Carbon\Carbon $startDate): array
    {
        $rules = \Modules\CRM\Models\WhatsAppAutomationRule::withoutGlobalScopes()
            ->where('workspace_id', $workspaceId);

        return [
            'total_rules'    => (clone $rules)->count(),
            'active_rules'   => (clone $rules)->where('is_active', true)->count(),
            'total_triggers' => (clone $rules)->sum('trigger_count'),
        ];
    }

    protected function getStartDate(string $period): \Carbon\Carbon
    {
        return match ($period) {
            'today'   => now()->startOfDay(),
            'week'    => now()->startOfWeek(),
            'month'   => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year'    => now()->startOfYear(),
            default   => now()->startOfMonth(),
        };
    }
}
