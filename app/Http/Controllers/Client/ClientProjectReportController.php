<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectReport;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProjectReportController extends Controller
{
    public function show(Request $request, Project $project, ProjectReport $report)
    {
        $this->authorize('view', $project);

        // Clients may only view reports that are published and whose scheduled time has passed.
        abort_unless(
            $project->id === $report->project_id
                && $report->published_at !== null
                && Carbon::parse($report->published_at)->lessThanOrEqualTo(now()),
            404,
        );

        return Inertia::render('Client/Projects/Report', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'report' => fn () => [
                'id' => $report->id,
                'title' => $report->title,
                'body' => $report->body,
                'published_at' => optional($report->published_at)->toIso8601String(),
            ],
        ]);
    }
}
