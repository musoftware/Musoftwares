<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class CreateMilestonesTool implements AiToolInterface
{
    public function name(): string { return 'create_milestones'; }
    public function description(): string { return 'Creates milestone phases for project roadmap.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'milestone_name' => ['type' => 'string'],
                'target_date'    => ['type' => 'string'],
            ],
            'required' => ['milestone_name'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $name = $arguments['milestone_name'] ?? 'Milestone';
        $date = $arguments['target_date'] ?? now('Africa/Cairo')->addDays(14)->toDateString();

        return [
            'success' => true,
            'action'  => 'Defined Milestone Phase',
            'detail'  => $name . ' (Target: ' . $date . ')',
        ];
    }
}
