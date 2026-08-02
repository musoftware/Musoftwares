<?php

namespace App\Services\AI;

use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;

class AiAdminWorkDispatcherService
{
    /**
     * Compile daily morning work dispatch report for developer (admin).
     */
    public function generateDailyDispatch(Project $project): array
    {
        $todayTasks = Task::where('project_id', $project->id)
            ->where('archived', false)
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END")
            ->latest()
            ->limit(10)
            ->get(['id', 'task_name', 'task_description', 'priority', 'due_date']);

        $pendingTodos = Todo::where('project_id', $project->id)
            ->where('completed', false)
            ->limit(10)
            ->get(['id', 'title', 'description', 'priority']);

        $unansweredQuestions = collect($project->ai_questions ?? [])
            ->where('answered', false)
            ->values()
            ->all();

        $recentActions = array_slice($project->ai_actions_log ?? [], 0, 5);

        return [
            'project_name'           => $project->project_name,
            'ai_understanding_pct'   => $project->ai_understanding_pct ?? 0,
            'todays_tasks'           => $todayTasks->toArray(),
            'pending_todos'          => $pendingTodos->toArray(),
            'client_waiting_on'      => $unansweredQuestions,
            'recent_ai_actions'      => $recentActions,
            'summary_for_programmer' => 'Focus today on top priority developer tasks extracted by AI Manager.',
        ];
    }
}
