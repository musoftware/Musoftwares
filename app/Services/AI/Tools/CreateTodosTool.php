<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;

class CreateTodosTool implements AiToolInterface
{
    public function name(): string
    {
        return 'create_todos';
    }

    public function description(): string
    {
        return 'Translates client chat messages into structured TODO tasks for the admin/programmer.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'todos' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title'       => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'priority'    => ['type' => 'string', 'enum' => ['low', 'medium', 'high', 'urgent']],
                        ],
                        'required' => ['title'],
                    ],
                ],
            ],
            'required' => ['todos'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $todosData = $arguments['todos'] ?? [];
        if (empty($todosData)) {
            return ['success' => false, 'action' => 'Create Developer TODOs', 'detail' => 'No items provided'];
        }

        $createdTitles = [];
        foreach ($todosData as $item) {
            $task = Task::create([
                'project_id'       => $project->id,
                'user_id'          => $project->user_id,
                'task_name'        => $item['title'],
                'task_description' => $item['description'] ?? 'Extracted by AI Manager from client conversation.',
                'priority'         => $item['priority'] ?? 'medium',
                'due_date'         => now('Africa/Cairo')->addDays(3)->toDateString(),
            ]);

            Todo::create([
                'project_id'  => $project->id,
                'user_id'     => $project->user_id,
                'task_id'     => $task->id,
                'title'       => $item['title'],
                'description' => $item['description'] ?? null,
                'priority'    => $item['priority'] ?? 'medium',
                'completed'   => false,
            ]);

            $createdTitles[] = $item['title'];
        }

        return [
            'success' => true,
            'action'  => 'Created ' . count($createdTitles) . ' Developer Task(s) for Admin',
            'detail'  => implode('; ', $createdTitles),
        ];
    }
}
