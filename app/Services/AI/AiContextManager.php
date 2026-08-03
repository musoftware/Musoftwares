<?php

namespace App\Services\AI;

use App\Models\Project;

class AiContextManager
{
    /**
     * Ensure context structure is standardized and initialized.
     */
    public function ensureStructuredContext(Project $project): array
    {
        $context = $project->ai_context ?? [];

        $defaults = [
            'current_stage'         => 'GREETING',
            'current_goal'          => $project->project_name ?? 'مشروع جديد',
            'pending_features'      => [],
            'completed_features'    => [],
            'tech_stack'            => [],
            'known_decisions'       => [],
            'decisions_log'         => [],
            'negotiation_history'   => [],
            'questions_asked_count' => 0,
            'summary'               => $project->description ?? 'لا يوجد وصف حتى الآن',
        ];

        $merged = array_replace_recursive($defaults, $context);

        if ($merged !== $context) {
            $project->update(['ai_context' => $merged]);
        }

        return $merged;
    }

    /**
     * Log an explicit project decision with timestamp and reason.
     */
    public function logDecision(Project $project, string $decisionTitle, string $reason): void
    {
        $context = $this->ensureStructuredContext($project);
        $logs    = $context['decisions_log'] ?? [];

        $logs[] = [
            'title'      => $decisionTitle,
            'reason'     => $reason,
            'decided_at' => now()->toIso8601String(),
        ];

        $context['decisions_log'] = array_slice($logs, -20); // Keep last 20 key decisions
        $project->update(['ai_context' => $context]);
    }

    /**
     * Log negotiation entry.
     */
    public function logNegotiation(Project $project, float $offeredPrice, string $clientReaction): void
    {
        $context = $this->ensureStructuredContext($project);
        $history = $context['negotiation_history'] ?? [];

        $history[] = [
            'offered_price' => $offeredPrice,
            'client_reaction' => $clientReaction,
            'timestamp'     => now()->toIso8601String(),
        ];

        $context['negotiation_history'] = $history;
        $project->update(['ai_context' => $context]);
    }

    /**
     * Update project stage safely.
     */
    public function updateStage(Project $project, string $newStage): void
    {
        $context = $this->ensureStructuredContext($project);
        $context['current_stage'] = strtoupper(trim($newStage));
        $project->update(['ai_context' => $context]);
    }

    /**
     * Perform periodic rolling context summary if history is long.
     */
    public function summarizeIfNeeded(Project $project, int $messageCount): void
    {
        if ($messageCount < 10) {
            return;
        }

        $context = $this->ensureStructuredContext($project);
        $pending = implode(', ', $context['pending_features'] ?? []);
        $done    = implode(', ', $context['completed_features'] ?? []);

        $newSummary = "مشروع: {$project->name}. المرحلة الحالية: {$context['current_stage']}. الخصائص المطلوبة: {$pending}. الخصائص المنفذة: {$done}.";

        $context['summary'] = $newSummary;
        $project->update(['ai_context' => $context]);
    }
}
