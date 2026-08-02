<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class SummarizeDiscussionTool implements AiToolInterface
{
    public function name(): string
    {
        return 'summarize_discussion';
    }

    public function description(): string
    {
        return 'Summarizes the chat discussion, updates project_type, current_goal, missing_info, and understanding percentage.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'project_type'     => ['type' => 'string'],
                'current_goal'     => ['type' => 'string'],
                'missing_info'     => ['type' => 'array', 'items' => ['type' => 'string']],
                'complexity'       => ['type' => 'string', 'enum' => ['Low', 'Medium', 'High', 'Custom Enterprise']],
                'understanding_pct' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
            ],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $summary = $project->ai_summary ?? [
            'project_type' => null,
            'features'     => [],
            'current_goal' => null,
            'missing_info' => [],
            'complexity'   => null,
        ];

        if (isset($arguments['project_type']))   $summary['project_type'] = $arguments['project_type'];
        if (isset($arguments['current_goal']))   $summary['current_goal'] = $arguments['current_goal'];
        if (isset($arguments['missing_info']))   $summary['missing_info'] = $arguments['missing_info'];
        if (isset($arguments['complexity']))     $summary['complexity']   = $arguments['complexity'];

        $updates = ['ai_summary' => $summary];

        if (isset($arguments['understanding_pct'])) {
            $updates['ai_understanding_pct'] = (int) $arguments['understanding_pct'];
        } else {
            // Auto calculate based on feature count & goal presence
            $featCount = count($summary['features'] ?? []);
            $calculatedPct = min(95, ($featCount * 15) + ($summary['current_goal'] ? 20 : 0));
            $updates['ai_understanding_pct'] = max($project->ai_understanding_pct ?? 0, $calculatedPct);
        }

        $project->update($updates);

        return [
            'success' => true,
            'action'  => 'Updated Project Understanding (' . $project->ai_understanding_pct . '%)',
            'detail'  => 'Goal: ' . ($summary['current_goal'] ?? 'In progress'),
        ];
    }
}
