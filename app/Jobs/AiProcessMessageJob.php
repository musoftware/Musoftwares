<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * AiProcessMessageJob
 *
 * Processes a new client message after it's saved.
 * Currently runs in simulation mode:
 *   - Increments ai_understanding_pct by 1 per message (capped at 95)
 *   - Extracts current_goal from the first non-system message
 *   - Logs an AI action entry
 *   - Posts a system acknowledgment if ai_enabled
 *
 * TODO: Replace simulation with real AI API (OpenAI/Gemini) call.
 */
class AiProcessMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $projectId,
        public readonly string $messageBody,
        public readonly int $authorId,
    ) {}

    public function handle(): void
    {
        $project = Project::find($this->projectId);
        if (!$project || !$project->ai_enabled) {
            return;
        }

        // --- Simulation: increment understanding % ---
        $currentPct = (int) ($project->ai_understanding_pct ?? 0);
        $newPct = min(95, $currentPct + random_int(2, 6));

        // --- Update ai_summary current_goal from message ---
        $summary = $project->ai_summary ?? [
            'project_type'  => null,
            'features'      => [],
            'current_goal'  => null,
            'missing_info'  => ['Budget', 'Timeline', 'Target audience'],
            'complexity'    => 'Unknown',
        ];

        // Simple heuristic: use first non-system user message as current_goal
        if (empty($summary['current_goal']) && !str_starts_with(trim($this->messageBody), '[System:')) {
            $summary['current_goal'] = mb_strimwidth(strip_tags($this->messageBody), 0, 120, '…');
        }

        // --- Append to AI actions log ---
        $actionsLog = $project->ai_actions_log ?? [];
        array_unshift($actionsLog, [
            'action'    => 'Noted client input',
            'detail'    => mb_strimwidth(strip_tags($this->messageBody), 0, 60, '…'),
            'timestamp' => now('Africa/Cairo')->toIso8601String(),
        ]);
        $actionsLog = array_slice($actionsLog, 0, 10); // keep last 10

        // --- Save updates ---
        $project->update([
            'ai_understanding_pct' => $newPct,
            'ai_summary'           => $summary,
            'ai_actions_log'       => $actionsLog,
        ]);

        // --- Post a system acknowledgment message ---
        $ackMessages = [
            '[System: AI noted your input and updated the project understanding.]',
            '[System: AI extracted key information from your message.]',
            '[System: AI updated project requirements based on your message.]',
            '[System: AI is analyzing your project needs.]',
        ];

        ProjectComment::create([
            'project_id'       => $project->id,
            'author_id'        => null,
            'guest_name'       => 'AI',
            'body'             => $ackMessages[array_rand($ackMessages)],
            'commentable_type' => Project::class,
            'commentable_id'   => $project->id,
            'parent_id'        => null,
        ]);
    }
}
