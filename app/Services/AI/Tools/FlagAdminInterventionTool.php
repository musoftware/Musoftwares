<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Models\Notification;

class FlagAdminInterventionTool implements AiToolInterface
{
    public function name(): string { return 'flag_admin_intervention'; }
    public function description(): string { return 'Flags urgent project items or decisions requiring admin technical intervention.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'reason'   => ['type' => 'string'],
                'priority' => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
            ],
            'required' => ['reason'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $reason = $arguments['reason'] ?? 'Admin intervention required.';
        $priority = $arguments['priority'] ?? 'high';

        // Create high-priority task for admin
        \App\Models\Task::create([
            'project_id'       => $project->id,
            'user_id'          => $project->user_id,
            'task_name'        => '🚨 Urgent Admin Intervention: ' . $project->project_name,
            'task_description' => $reason,
            'priority'         => $priority,
            'due_date'         => now('Africa/Cairo')->toDateString(),
        ]);

        return [
            'success' => true,
            'action'  => 'Flagged Urgent Admin Intervention',
            'detail'  => $reason,
        ];
    }
}
