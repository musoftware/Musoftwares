<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Models\Task;

class UpdatePrioritiesTool implements AiToolInterface
{
    public function name(): string { return 'update_priorities'; }
    public function description(): string { return 'Adjusts task priorities based on client feedback.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'task_title' => ['type' => 'string'],
                'priority'   => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
            ],
            'required' => ['task_title', 'priority'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $title    = $arguments['task_title'] ?? '';
        $priority = $arguments['priority'] ?? 'high';

        Task::where('project_id', $project->id)
            ->where('task_name', 'like', '%' . $title . '%')
            ->update(['priority' => $priority]);

        return [
            'success' => true,
            'action'  => 'Adjusted Task Priority (' . ucfirst($priority) . ')',
            'detail'  => $title,
        ];
    }
}
