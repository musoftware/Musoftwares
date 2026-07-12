<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Project\StoreReportRequest;
use App\Http\Requests\Admin\Project\UpdateReportRequest;
use App\Models\Project;
use App\Models\ProjectReport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

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
            'published_at' => $r->published_at ? $r->published_at->copy()->setTimezone('Africa/Cairo')->toDateTimeString() : null,
            'is_published' => $r->isPublished(),
            'notify_client' => (bool) $r->notify_client,
            'period_start' => $r->period_start ? $r->period_start->copy()->setTimezone('Africa/Cairo')->toDateString() : null,
            'period_end' => $r->period_end ? $r->period_end->copy()->setTimezone('Africa/Cairo')->toDateString() : null,
            'created_at' => $r->created_at ? $r->created_at->copy()->setTimezone('Africa/Cairo')->toDateTimeString() : null,
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
        $data = $request->validated();
        if (!empty($data['published_at'])) {
            $data['published_at'] = Carbon::parse($data['published_at'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString();
        }
        if (!empty($data['period_start'])) {
            $data['period_start'] = Carbon::parse($data['period_start'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString();
        }
        if (!empty($data['period_end'])) {
            $data['period_end'] = Carbon::parse($data['period_end'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString();
        }

        $project->reports()->create(array_merge($data, [
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
                'period_start' => $report->period_start ? $report->period_start->copy()->setTimezone('Africa/Cairo')->toDateString() : null,
                'period_end' => $report->period_end ? $report->period_end->copy()->setTimezone('Africa/Cairo')->toDateString() : null,
                'published_at' => $report->published_at ? $report->published_at->copy()->setTimezone('Africa/Cairo')->format('Y-m-d\TH:i') : '',
                'notify_client' => (bool) $report->notify_client,
            ],
            'types' => ProjectReport::TYPES,
            'priorities' => ProjectReport::PRIORITIES,
        ]);
    }

    public function update(UpdateReportRequest $request, Project $project, ProjectReport $report)
    {
        abort_unless($report->project_id === $project->id, 404);
        $data = $request->validated();
        if (array_key_exists('published_at', $data)) {
            $data['published_at'] = $data['published_at'] ? Carbon::parse($data['published_at'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
        }
        if (array_key_exists('period_start', $data)) {
            $data['period_start'] = $data['period_start'] ? Carbon::parse($data['period_start'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
        }
        if (array_key_exists('period_end', $data)) {
            $data['period_end'] = $data['period_end'] ? Carbon::parse($data['period_end'], 'Africa/Cairo')->setTimezone('UTC')->toDateTimeString() : null;
        }

        $report->update($data);

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
