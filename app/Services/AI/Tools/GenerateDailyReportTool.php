<?php

namespace App\Services\AI\Tools;

use App\Models\Project;
use App\Models\ProjectReport;

class GenerateDailyReportTool implements AiToolInterface
{
    public function name(): string { return 'generate_daily_report'; }
    public function description(): string { return 'Compiles daily developer & project progress report for the admin.'; }
    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'summary' => ['type' => 'string'],
            ],
            'required' => ['summary'],
        ];
    }

    public function execute(Project $project, array $arguments): array
    {
        $summaryText = $arguments['summary'] ?? 'Daily progress summary generated automatically by AI Manager.';

        ProjectReport::create([
            'project_id'   => $project->id,
            'title'        => 'AI Daily Developer Report — ' . now('Africa/Cairo')->toDateString(),
            'content'      => $summaryText,
            'published_at' => now('Africa/Cairo'),
        ]);

        return [
            'success' => true,
            'action'  => 'Generated Daily Developer Report',
            'detail'  => 'Published to admin & project archive',
        ];
    }
}
