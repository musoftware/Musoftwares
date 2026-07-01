<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Client\Concerns\ResolvesClientProject;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientProjectTaskController extends Controller
{
    use ResolvesClientProject;

    public function tasksIndex(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $tasks = $project->tasks()
            ->when($project->hide_future_tasks, function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('due_date')->orWhere('due_date', '<=', now()->toDateString());
                });
            })
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Client/Projects/Tasks', [
            'project' => ['id' => $project->id, 'name' => $project->project_name],
            'tasks' => fn () => $tasks,
            'hideFuture' => (bool) $project->hide_future_tasks,
        ]);
    }
}
