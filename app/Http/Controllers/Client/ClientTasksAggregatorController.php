<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientTasksAggregatorController extends Controller
{
    /**
     * Aggregated cross-project view of every task and todo owned by the user,
     * grouped by their due / scheduled day so the client can see "what's on
     * today / this week / overdue" in a single place. Read-only by design —
     * mutating a card still happens inside the project's board.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $showCompleted = $request->boolean('completed');
        $projectFilter = $request->integer('project_id') ?: null;

        $projectQuery = Project::query()
            ->where('user_id', $userId)
            ->when(! $request->boolean('with_archived'), fn (Builder $q) => $q->where('archived', 0))
            ->orderBy('project_name');

        $projects = $projectQuery->get(['id', 'project_name', 'archived', 'hide_future_tasks']);

        $projectIds = $projects->pluck('id');

        $projectsById = $projects->keyBy('id');

        // Walk the user's projects and pull every task that isn't hidden by the per-project future-tasks flag.
        // We mirror `ClientProjectTaskController::tasksIndex` so behaviour stays identical to the per-project page.
        $projectsWithHideFuture = $projects->where('hide_future_tasks', true)->pluck('id');
        $todayString = Carbon::today()->toDateString();

        $tasks = Task::query()
            ->with(['project:id,project_name,archived', 'task_todo_items:id,task_id,completed'])
            ->whereIn('project_id', $projectIds)
            ->when($projectFilter, fn (Builder $q) => $q->where('project_id', $projectFilter))
            ->when($projectsWithHideFuture->isNotEmpty(), function (Builder $q) use ($projectsWithHideFuture, $todayString) {
                $q->where(function (Builder $inner) use ($projectsWithHideFuture, $todayString) {
                    $inner->whereNotIn('project_id', $projectsWithHideFuture)
                        ->orWhere(function (Builder $qq) use ($todayString) {
                            $qq->whereNull('due_date')->orWhere('due_date', '<=', $todayString);
                        });
                });
            })
            ->when(! $showCompleted, function (Builder $q) {
                $q->where(function (Builder $qq) {
                    $qq->whereDoesntHave('task_todo_items')
                        ->orWhereHas('task_todo_items', fn (Builder $t) => $t->where('completed', false));
                });
            })
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderBy('id', 'desc')
            ->get();

        $todos = Todo::query()
            ->with(['project:id,project_name,archived', 'task:id,task_name'])
            ->whereIn('project_id', $projectIds)
            ->when($projectFilter, fn (Builder $q) => $q->where('project_id', $projectFilter))
            ->when(! $showCompleted, fn (Builder $q) => $q->where(function (Builder $qq) {
                $qq->where('completed', false)->orWhereNull('completed');
            }))
            // Skip todos that already roll up under a task — we render them inside the parent task to avoid duplicates.
            ->whereNotNull('task_id')
            ->orderByRaw('end_at IS NULL, end_at ASC')
            ->orderBy('id', 'desc')
            ->get();

        $taskItems = $tasks->map(function (Task $task) use ($projectsById) {
            $todos = $task->task_todo_items;
            $total = $todos->count();
            $done = $todos->where('completed', true)->count();

            return [
                'kind' => 'task',
                'id' => $task->id,
                'title' => $task->task_name,
                'description' => $task->task_description,
                'project_id' => $task->project_id,
                'project_name' => $projectsById[$task->project_id]->project_name ?? ($task->project->project_name ?? ''),
                'priority' => $task->priority,
                'due_date' => $task->due_date,
                'progress' => $total > 0 ? (int) round($done * 100 / $total) : null,
                'todo_total' => $total,
                'todo_done' => $done,
                'completed' => $total > 0 && $done === $total,
            ];
        })->values();

        $todoItems = $todos->map(function (Todo $todo) use ($projectsById) {
            $day = $todo->end_at ?: ($todo->start_at ?: null);

            return [
                'kind' => 'todo',
                'id' => $todo->id,
                'title' => $todo->title,
                'description' => $todo->description,
                'project_id' => $todo->project_id,
                'project_name' => $projectsById[$todo->project_id]->project_name ?? ($todo->project->project_name ?? ''),
                'priority' => $todo->priority,
                'due_date' => $day ? Carbon::parse($day)->toDateString() : null,
                'completed' => (bool) $todo->completed,
                'parent_task_id' => $todo->task_id,
                'parent_task_name' => $todo->task?->task_name,
                'start_at' => $todo->start_at,
                'end_at' => $todo->end_at,
            ];
        })->values();

        $items = $taskItems->merge($todoItems)->values();

        return Inertia::render('Client/Projects/TasksAggregator', [
            'projects' => fn () => $projects->map(fn (Project $p) => [
                'id' => $p->id,
                'name' => $p->project_name,
                'archived' => (bool) $p->archived,
            ])->values(),
            'items' => fn () => $items,
            'filters' => [
                'project_id' => $projectFilter,
                'completed' => $showCompleted,
                'with_archived' => (bool) $request->boolean('with_archived'),
            ],
            'stats' => [
                'total' => $items->count(),
                'tasks' => $taskItems->count(),
                'todos' => $todoItems->count(),
                'completed' => $items->where('completed', true)->count(),
            ],
        ]);
    }
}
