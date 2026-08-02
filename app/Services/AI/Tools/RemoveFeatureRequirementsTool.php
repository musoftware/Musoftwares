<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class RemoveFeatureRequirementsTool implements AiToolInterface
{
    public function name(): string
    {
        return 'remove_feature_requirements';
    }

    public function description(): string
    {
        return 'Removes specific feature requirements when requested by the client.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'features' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => 'Features to remove from project scope',
                ],
            ],
            'required' => ['features'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $toRemove = $arguments['features'] ?? [];
        if (empty($toRemove)) {
            return ['success' => false, 'action' => 'Remove Requirements', 'detail' => 'No features specified for removal'];
        }

        $summary = $project->ai_summary ?? [];
        $existing = $summary['features'] ?? [];

        $updated = array_values(array_filter($existing, function ($feat) use ($toRemove) {
            foreach ($toRemove as $rem) {
                if (mb_stripos($feat, $rem) !== false || mb_stripos($rem, $feat) !== false) {
                    return false;
                }
            }
            return true;
        }));

        $summary['features'] = $updated;
        $project->update(['ai_summary' => $summary]);

        return [
            'success' => true,
            'action'  => 'Removed feature requirement(s)',
            'detail'  => implode(', ', $toRemove),
        ];
    }
}
