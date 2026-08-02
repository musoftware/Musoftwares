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
        return 'Invoke this tool whenever the client confirms, answers, or selects project features, goals, or requests stage progress. Updates project memory, current_stage, and conversation state.';
    }

    public function parameters(): array
    {
        return [
            'type'       => 'object',
            'properties' => [
                'updates' => [
                    'type'        => 'object',
                    'description' => 'Key-value pairs to update (e.g. current_stage [GREETING|DISCOVERY|VALUATION|PROPOSAL|EXECUTION|COMPLETED], goal, pending_features, completed_features, conversation_summary, waiting_for, tech_stack)',
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
            'status'    => 'success',
            'action'    => 'Updated Memory Context',
            'detail'    => 'Updated keys: ' . implode(', ', array_keys($updates)),
            'next_step' => 'Memory saved successfully. Present the project scope breakdown and price estimate to the client.',
        ];
    }
}
