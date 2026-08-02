<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class CreateFeatureRequirementsTool implements AiToolInterface
{
    public function name(): string
    {
        return 'create_feature_requirements';
    }

    public function description(): string
    {
        return 'Extracts and adds new project features/requirements to the project AI summary.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'features' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'List of feature requirements extracted from chat',
                ],
            ],
            'required' => ['features'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $newFeatures = $arguments['features'] ?? [];
        if (empty($newFeatures)) {
            return ['success' => false, 'action' => 'Add Requirements', 'detail' => 'No features provided'];
        }

        $summary = $project->ai_summary ?? [
            'project_type' => null,
            'features'     => [],
            'current_goal' => null,
            'missing_info' => [],
            'complexity'   => null,
        ];

        $existing = $summary['features'] ?? [];
        $merged = array_unique(array_merge($existing, $newFeatures));
        $summary['features'] = array_values($merged);

        $project->update(['ai_summary' => $summary]);

        return [
            'success' => true,
            'action'  => 'Extracted ' . count($newFeatures) . ' requirement(s)',
            'detail'  => implode(', ', $newFeatures),
        ];
    }
}
