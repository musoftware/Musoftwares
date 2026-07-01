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
            'body' => $r->body,
            'published_at' => $r->published_at ? $r->published_at->toDateTimeString() : null,
            'is_published' => $r->isPublished(),
            'created_at' => $r->created_at?->toDateTimeString(),
        ]);

        return Inertia::render('Admin/Projects/Reports/Index', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'reports' => $reports,
        ]);
    }

    public function store(StoreReportRequest $request, Project $project)
    {
        $project->reports()->create(array_merge($request->validated(), [
            'author_id' => $request->user()->id,
        ]));

        return redirect()->back()->with('success', __('general.report_created'));
    }

    public function update(UpdateReportRequest $request, Project $project, ProjectReport $report)
    {
        abort_unless($report->project_id === $project->id, 404);
        $report->update($request->validated());

        return redirect()->back()->with('success', __('general.report_updated'));
    }

    public function destroy(Request $request, Project $project, ProjectReport $report)
    {
        abort_unless($report->project_id === $project->id, 404);
        $report->delete();

        return redirect()->back()->with('success', __('general.report_deleted'));
    }
}
