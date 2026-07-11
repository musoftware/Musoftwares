<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\StoreReportRequest;
use App\Http\Requests\Admin\Project\UpdateReportRequest;
use App\Models\Project;
use App\Models\ProjectReport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectReportController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $reports = $project->reports()->latest('published_at')->latest()->get()->map(fn (ProjectReport $r) => [
            'id' => $r->id,
            'title' => $r->title,
            'type' => $r->type,
            'priority' => $r->priority,
            'summary' => $r->summary,
            'body' => $r->body,
            'published_at' => $r->published_at ? $r->published_at->toDateTimeString() : null,
            'is_published' => $r->isPublished(),
            'notify_client' => (bool) $r->notify_client,
            'period_start' => $r->period_start?->toDateString(),
            'period_end' => $r->period_end?->toDateString(),
            'created_at' => $r->created_at?->toDateTimeString(),
        ]);

        return Inertia::render('Admin/Projects/Reports/Index', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'reports' => $reports,
        ]);
    }

    public function create(Request $request, Project $project)
    {
        return Inertia::render('Admin/Projects/Reports/Create', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'types' => ProjectReport::TYPES,
            'priorities' => ProjectReport::PRIORITIES,
        ]);
    }

    public function store(StoreReportRequest $request, Project $project)
    {
        $project->reports()->create(array_merge($request->validated(), [
            'author_id' => $request->user()->id,
        ]));

        return redirect()
            ->route('admin.projects.reports.index', $project->id)
            ->with('success', __('general.report_created'));
    }

    public function edit(Request $request, Project $project, ProjectReport $report)
    {
        abort_unless($report->project_id === $project->id, 404);

        return Inertia::render('Admin/Projects/Reports/Edit', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'report' => [
                'id' => $report->id,
                'title' => $report->title,
                'type' => $report->type,
                'priority' => $report->priority,
                'summary' => $report->summary,
                'body' => $report->body,
                'period_start' => $report->period_start?->toDateString(),
                'period_end' => $report->period_end?->toDateString(),
                'published_at' => $report->published_at ? $report->published_at->format('Y-m-d\TH:i') : '',
                'notify_client' => (bool) $report->notify_client,
            ],
            'types' => ProjectReport::TYPES,
            'priorities' => ProjectReport::PRIORITIES,
        ]);
    }

    public function update(UpdateReportRequest $request, Project $project, ProjectReport $report)
    {
        abort_unless($report->project_id === $project->id, 404);
        $report->update($request->validated());

        return redirect()
            ->route('admin.projects.reports.index', $project->id)
            ->with('success', __('general.report_updated'));
    }

    public function destroy(Request $request, Project $project, ProjectReport $report)
    {
        abort_unless($report->project_id === $project->id, 404);
        $report->delete();

        return redirect()->back()->with('success', __('general.report_deleted'));
    }
}
