<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class UpdateContextTool implements AiToolInterface
{
    public function name(): string
    {
        return 'update_context';
    }

    public function description(): string
    {
        return 'Update project context incrementally (e.g. current_stage, pending_features, completed_features, tech_stack, known_decisions).';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'updates' => [
                    'type'        => 'object',
                    'description' => 'Key-value pairs to update inside project context',
                ],
            ],
            'required'   => ['updates'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $updates = $arguments['updates'] ?? [];
        if (!empty($updates) && is_array($updates)) {
            $project->updateAiContext($updates);
        }

        return [
            'status' => 'success',
            'action' => 'Updated Project Context',
            'detail' => 'Updated keys: ' . implode(', ', array_keys($updates)),
        ];
    }
}
