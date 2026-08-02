<?php

namespace App\Services\AI\Tools;

use App\Models\Project;

class UpdateProjectDetailsTool implements AiToolInterface
{
    public function name(): string
    {
        return 'update_project_details';
    }

    public function description(): string
    {
        return 'Updates project metadata such as title, status, budget, or target completion date.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'budget' => ['type' => 'number', 'description' => 'Updated project budget amount'],
                'status' => ['type' => 'string', 'enum' => ['open', 'hold_on', 'closed']],
                'date_end' => ['type' => 'string', 'description' => 'Target completion date YYYY-MM-DD'],
            ],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $updates = [];
        $details = [];

        if (isset($arguments['budget'])) {
            $updates['budget'] = (float) $arguments['budget'];
            $details[] = 'Budget updated to ' . number_format($arguments['budget'], 2);
        }

        if (isset($arguments['status'])) {
            $updates['status'] = $arguments['status'];
            $details[] = 'Status changed to ' . $arguments['status'];
        }

        if (isset($arguments['date_end'])) {
            $updates['date_end'] = $arguments['date_end'];
            $details[] = 'End date set to ' . $arguments['date_end'];
        }

        if (!empty($updates)) {
            $project->update($updates);
        }

        $detailStr = implode(', ', $details) ?: 'No changes required';

        return [
            'success' => true,
            'action'  => 'Updated project details',
            'detail'  => $detailStr,
        ];
    }
}
