<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\ERPTodoItem;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * ERP Task Controller — admin/tenant manages task boards for clients.
 * Recovered from old project: Admin/TaskController + Admin/TodoController.
 *
 * Workflow:
 * 1. Admin creates a Task (board) for a client/project
 * 2. Admin adds Todo items to the task
 * 3. Client sees the task and todos in their client dashboard
 * 4. Both admin and client can mark todos complete
 * 5. Paid todos (is_paid=true) cannot be deleted
 */
class TaskController extends Controller
{
    // ── Task Board CRUD ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = ERPTask::with(['client', 'project', 'creator'])
            ->withCount(['items', 'items as completed_items_count' => fn($q) => $q->where('completed', true)]);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if (!$request->boolean('show_archived')) {
            $query->where('archived', false);
        }

        $tasks = $query->latest()->paginate(20)->withQueryString()
            ->through(fn($task) => $this->shapeTask($task));

        return Inertia::render('ERP/Tasks/Index', [
            'tasks'   => $tasks,
            'clients' => TenantClient::select('id', 'name')->get(),
            'filters' => $request->only(['client_id', 'status', 'show_archived']),
        ]);
    }

    public function show(ERPTask $task)
    {
        $task->load([
            'client',
            'project',
            'creator',
            'items' => fn($q) => $q->whereNull('parent_id')
                ->with(['children' => fn($q) => $q->orderBy('sort_index')->orderBy('id')])
                ->orderBy('completed')
                ->orderBy('sort_index')
                ->orderBy('id', 'desc'),
        ]);

        return Inertia::render('ERP/Tasks/Show', [
            'task'  => $this->shapeTask($task),
            'todos' => $task->items->map(fn($item) => $this->shapeTodo($item)),
            'completion' => $task->completionPercentage(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_name'       => 'required|string|max:255',
            'task_description'=> 'nullable|string',
            'client_id'       => 'nullable|exists:tenant_clients,id',
            'project_id'      => 'nullable|exists:erp_projects,id',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'due_date'        => 'nullable|date',
        ]);

        $task = ERPTask::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'status'     => 'open',
        ]));

        return redirect()->route('erp.tasks.show', $task->id)
            ->with('success', 'Task created.');
    }

    public function update(Request $request, ERPTask $task)
    {
        $validated = $request->validate([
            'task_name'       => 'required|string|max:255',
            'task_description'=> 'nullable|string',
            'client_id'       => 'nullable|exists:tenant_clients,id',
            'project_id'      => 'nullable|exists:erp_projects,id',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'status'          => 'nullable|in:open,in_progress,completed,archived',
            'due_date'        => 'nullable|date',
        ]);

        $task->update($validated);

        return back()->with('success', 'Task updated.');
    }

    public function archive(ERPTask $task)
    {
        $task->update(['archived' => true]);
        return back()->with('success', 'Task archived.');
    }

    public function unarchive(ERPTask $task)
    {
        $task->update(['archived' => false]);
        return back()->with('success', 'Task restored.');
    }

    public function destroy(ERPTask $task)
    {
        $task->delete();
        return redirect()->route('erp.tasks.index')->with('success', 'Task deleted.');
    }

    // ── Todo Item CRUD ───────────────────────────────────────────────

    /**
     * Add a todo item to a task.
     * Recovered from old project: Admin/TodoController::store()
     */
    public function storeItem(Request $request, ERPTask $task)
    {
        $validated = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'priority'       => 'nullable|in:low,normal,high,urgent',
            'priority_color' => 'nullable|string|max:10',
            'tags'           => 'nullable|array',
            'cost'           => 'nullable|numeric|min:0',
            'cost_currency'  => 'nullable|string|size:3',
            'start_at'       => 'nullable|date',
            'end_at'         => 'nullable|date',
            'parent_id'      => 'nullable|exists:erp_todo_items,id',
        ]);

        $item = ERPTodoItem::create(array_merge($validated, [
            'tenant_id'  => $task->tenant_id,
            'task_id'    => $task->id,
            'sort_index' => ERPTodoItem::where('task_id', $task->id)->max('sort_index') + 1,
        ]));

        return response()->json([
            'success' => true,
            'item'    => $this->shapeTodo($item),
        ]);
    }

    /**
     * Update a todo item inline.
     * Recovered from old project: set_title, set_priority, set_description actions.
     */
    public function updateItem(Request $request, ERPTask $task, ERPTodoItem $item)
    {
        $validated = $request->validate([
            'title'          => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'priority'       => 'nullable|in:low,normal,high,urgent',
            'priority_color' => 'nullable|string|max:10',
            'tags'           => 'nullable|array',
            'cost'           => 'nullable|numeric|min:0',
            'cost_currency'  => 'nullable|string|size:3',
            'start_at'       => 'nullable|date',
            'end_at'         => 'nullable|date',
        ]);

        $item->update($validated);

        return response()->json(['success' => true, 'item' => $this->shapeTodo($item->fresh())]);
    }

    /**
     * Toggle complete/incomplete on a todo item.
     * Recovered from old project: Admin/TodoController::complete()
     */
    public function completeItem(Request $request, ERPTask $task, ERPTodoItem $item)
    {
        if ($request->boolean('completed')) {
            $item->markComplete();
        } else {
            $item->markIncomplete();
        }

        return response()->json(['success' => true, 'item' => $this->shapeTodo($item->fresh())]);
    }

    /**
     * Drag-and-drop reorder todo items.
     * Recovered from old project: Admin/TodoController::sort()
     */
    public function sortItems(Request $request, ERPTask $task)
    {
        $request->validate(['sort' => 'required|array']);

        foreach ($request->input('sort') as $index => $itemId) {
            $item = ERPTodoItem::where('task_id', $task->id)->find($itemId);
            if ($item && !$item->completed) {
                $item->update(['sort_index' => $index]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Pause a todo item.
     * Recovered from old project: Admin/TodoController::pause()
     */
    public function pauseItem(ERPTask $task, ERPTodoItem $item)
    {
        $item->pause();
        return response()->json(['success' => true]);
    }

    /**
     * Resume a paused todo item.
     * Recovered from old project: Admin/TodoController::resume()
     */
    public function resumeItem(ERPTask $task, ERPTodoItem $item)
    {
        $item->resume();
        return response()->json(['success' => true]);
    }

    /**
     * Delete a todo item — guards against deleting paid items.
     * Recovered from old project: Admin/TodoController::delete()
     */
    public function destroyItem(ERPTask $task, ERPTodoItem $item)
    {
        if (!$item->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Paid items cannot be deleted.',
            ], 403);
        }

        $item->delete();
        return response()->json(['success' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function shapeTask(ERPTask $task): array
    {
        $totalItems     = $task->items_count ?? $task->items()->count();
        $completedItems = $task->completed_items_count ?? $task->items()->where('completed', true)->count();
        $progress       = $totalItems > 0 ? round(($completedItems / $totalItems) * 100, 0) : null;

        return [
            'id'             => $task->id,
            'task_name'      => $task->task_name,
            'task_description'=> $task->task_description,
            'status'         => $task->status,
            'priority'       => $task->priority,
            'archived'       => $task->archived,
            'due_date'       => $task->due_date?->format('M j, Y'),
            'created_at'     => $task->created_at->format('M j, Y'),
            'client'         => $task->client ? ['id' => $task->client->id, 'name' => $task->client->name] : null,
            'project'        => $task->project ? ['id' => $task->project->id, 'name' => $task->project->name] : null,
            'creator'        => $task->creator?->name,
            'todos_count'    => $totalItems - $completedItems,
            'total_todos'    => $totalItems,
            'completed_todos'=> $completedItems,
            'progress'       => $progress,
        ];
    }

    private function shapeTodo(ERPTodoItem $item): array
    {
        return [
            'id'             => $item->id,
            'title'          => $item->title,
            'description'    => $item->description,
            'completed'      => $item->completed,
            'completed_at'   => $item->completed_at?->toISOString(),
            'priority'       => $item->priority,
            'priority_color' => $item->priority_color,
            'sort_index'     => $item->sort_index,
            'paused'         => $item->paused,
            'is_paid'        => $item->is_paid,
            'cost'           => $item->cost,
            'cost_currency'  => $item->cost_currency,
            'start_at'       => $item->start_at?->toISOString(),
            'end_at'         => $item->end_at?->toISOString(),
            'tags'           => $item->tags ?? [],
            'parent_id'      => $item->parent_id,
            'children'       => $item->relationLoaded('children')
                ? $item->children->map(fn($c) => $this->shapeTodo($c))->toArray()
                : [],
            'created_at'     => $item->created_at->toISOString(),
        ];
    }
}
