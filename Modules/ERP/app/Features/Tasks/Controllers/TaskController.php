<?php

namespace Modules\ERP\app\Features\Tasks\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\ERPTodoItem;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Closure;
use Inertia\Inertia;
use App\Services\ActivityService;

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
class TaskController extends Controller implements HasMiddleware
{
    // ── Tenant resolution ─────────────────────────────────────────

    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, Closure $next) {
                $user = Auth::user();
                if (Auth::guard('erp_team')->check()) {
                    $user = Auth::guard('erp_team')->user()->tenant->user;
                }
                if (!$user || !$user->hasModuleSubscription('erp-tasks')) {
                    if ($request->expectsJson() || $request->is('api/*')) {
                        return response()->json(['message' => 'Tasks addon required.'], 403);
                    }
                    return Inertia::render('ERP/UpgradePreview', ['module' => 'erp-tasks']);
                }
                return $next($request);
            }),
        ];
    }

    private function resolveTenant(): Tenant
    {
        if (Auth::guard('erp_team')->check()) {
            return Auth::guard('erp_team')->user()->tenant;
        }
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    // ── Task Board CRUD ──────────────────────────────────────────────

    public function index(Request $request)
    {
        $tenant = $this->resolveTenant();

        $query = ERPTask::where('tenant_id', $tenant->id)
            ->with(['client', 'project', 'creator'])
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
            'tasks'    => $tasks,
            'clients'  => TenantClient::where('tenant_id', $tenant->id)->select('id', 'name')->get(),
            'projects' => Project::where('tenant_id', $tenant->id)->select('id', 'name', 'client_id')->get(),
            'filters'  => $request->only(['client_id', 'status', 'show_archived']),
        ]);
    }

    public function asList(Request $request)
    {
        $tenant = $this->resolveTenant();

        $todos = ERPTodoItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('completed', false)
            ->where('paused', false)
            ->whereNull('parent_id')
            ->whereHas('task', function ($q) {
                $q->where('archived', false);
            })
            ->with(['task.client', 'task.creator', 'children' => function($q) {
                $q->orderBy('sort_index')->orderBy('id');
            }])
            ->orderBy('id')
            ->get();

        $data = [];

        foreach ($todos as $todo) {
            $task = $todo->task;
            if (!$task || !$task->client) {
                continue;
            }

            $clientId = $task->client->id;
            $taskId = $task->id;

            if (!isset($data[$clientId])) {
                $data[$clientId] = [
                    'client' => [
                        'id' => $task->client->id,
                        'name' => $task->client->name,
                        'email' => $task->client->email,
                    ],
                    'tasks' => []
                ];
            }

            if (!isset($data[$clientId]['tasks'][$taskId])) {
                $data[$clientId]['tasks'][$taskId] = [
                    'id' => $task->id,
                    'task_name' => $task->task_name,
                    'status' => $task->status,
                    'client_id' => $task->client_id,
                    'todos' => []
                ];
            }

            $data[$clientId]['tasks'][$taskId]['todos'][] = $this->shapeTodo($todo);
        }

        // Clean up associative arrays to indexed arrays for JSON mapping
        $result = array_values($data);
        foreach ($result as &$clientData) {
            $clientData['tasks'] = array_values($clientData['tasks']);
        }

        return Inertia::render('ERP/Tasks/AsList', [
            'arrangedClients' => $result,
        ]);
    }

    public function show(ERPTask $task)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to task.');
        }

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
            'currencies' => \App\Models\Currency::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'task_name'       => 'sometimes|required|string|max:255',
            'title'           => 'sometimes|required|string|max:255',
            'task_description'=> 'nullable|string',
            'client_id'       => 'nullable|exists:erp_tenant_clients,id',
            'project_id'      => 'nullable|exists:erp_projects,id',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'status'          => 'nullable|in:open,in_progress,review,completed,archived',
            'due_date'        => 'nullable|date',
        ]);
        
        $tenant = $this->resolveTenant();

        // Ensure the client and project belong to the tenant
        if (!empty($validated['client_id'])) {
            TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        }
        if (!empty($validated['project_id'])) {
            Project::where('tenant_id', $tenant->id)->findOrFail($validated['project_id']);
        }

        $taskName = $request->input('task_name') ?? $request->input('title');

        $task = ERPTask::create(array_merge($validated, [
            'tenant_id'  => $tenant->id,
            'task_name'  => $taskName,
            'created_by' => Auth::id(),
            'status'     => $request->input('status', 'open'),
        ]));

        ActivityService::log(
            event: 'task.created',
            description: "Created task board: {$task->task_name}",
            subject: $task,
            workspace: 'erp'
        );

        if ($request->input('quick_add') || $request->ajax() || $request->has('redirect_back')) {
            return redirect()->back()->with('success', 'Task created.');
        }

        return redirect()->route('erp.tasks.show', $task->id)
            ->with('success', 'Task created.');
    }

    public function update(Request $request, ERPTask $task)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to task.');
        }

        $validated = $request->validate([
            'task_name'       => 'sometimes|required|string|max:255',
            'task_description'=> 'nullable|string',
            'client_id'       => 'nullable|exists:erp_tenant_clients,id',
            'project_id'      => 'nullable|exists:erp_projects,id',
            'priority'        => 'nullable|in:low,normal,high,urgent',
            'status'          => 'nullable|in:open,in_progress,review,completed,archived',
            'due_date'        => 'nullable|date',
        ]);

        if (!empty($validated['client_id'])) {
            TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        }
        if (!empty($validated['project_id'])) {
            Project::where('tenant_id', $tenant->id)->findOrFail($validated['project_id']);
        }

        $task->update($validated);

        return back()->with('success', 'Task updated.');
    }

    public function archive(ERPTask $task)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to task.');
        }

        $task->update(['archived' => true]);
        return back()->with('success', 'Task archived.');
    }

    public function unarchive(ERPTask $task)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to task.');
        }

        $task->update(['archived' => false]);
        return back()->with('success', 'Task restored.');
    }

    public function destroy(ERPTask $task)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to task.');
        }

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
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to task.');
        }

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

        if (!empty($validated['parent_id'])) {
            // Ensure parent item belongs to the same task
            ERPTodoItem::where('task_id', $task->id)->findOrFail($validated['parent_id']);
        }

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
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id || $item->task_id !== $task->id) {
            abort(403, 'Unauthorized access.');
        }

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
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id || $item->task_id !== $task->id) {
            abort(403, 'Unauthorized access.');
        }

        if ($request->boolean('completed')) {
            $item->markComplete();
            ActivityService::log(
                event: 'task.completed',
                description: "Completed todo item: {$item->title}",
                subject: $item,
                workspace: 'erp'
            );
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
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access.');
        }

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
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id || $item->task_id !== $task->id) {
            abort(403, 'Unauthorized access.');
        }

        $item->pause();
        return response()->json(['success' => true]);
    }

    /**
     * Resume a paused todo item.
     * Recovered from old project: Admin/TodoController::resume()
     */
    public function resumeItem(ERPTask $task, ERPTodoItem $item)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id || $item->task_id !== $task->id) {
            abort(403, 'Unauthorized access.');
        }

        $item->resume();
        return response()->json(['success' => true]);
    }

    /**
     * Delete a todo item — guards against deleting paid items.
     * Recovered from old project: Admin/TodoController::delete()
     */
    public function destroyItem(ERPTask $task, ERPTodoItem $item)
    {
        $tenant = $this->resolveTenant();
        if ($task->tenant_id !== $tenant->id || $item->task_id !== $task->id) {
            abort(403, 'Unauthorized access.');
        }

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
