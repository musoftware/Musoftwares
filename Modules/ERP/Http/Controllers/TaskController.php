<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\ERPTodoItem;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\ERP\Transformers\TaskResource;
use Modules\ERP\Transformers\TodoItemResource;
use Modules\ERP\Http\Requests\StoreTaskRequest;
use Modules\ERP\Http\Requests\UpdateTaskRequest;
use Modules\ERP\Services\TaskService;

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
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    // ── Tenant resolution ─────────────────────────────────────────

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
        $filters = $request->only(['client_id', 'status', 'show_archived']);

        $tasks = $this->taskService->getPaginatedTasks($tenant->id, $filters)
            ->withQueryString()
            ->through(fn($task) => TaskResource::make($task)->resolve());

        return Inertia::render('ERP/Tasks/Index', [
            'tasks'    => $tasks,
            'clients'  => TenantClient::where('tenant_id', $tenant->id)->select('id', 'name')->get(),
            'projects' => Project::where('tenant_id', $tenant->id)->select('id', 'name', 'client_id')->get(),
            'filters'  => $filters,
        ]);
    }

    public function asList(Request $request)
    {
        $tenant = $this->resolveTenant();

        $arrangedClients = $this->taskService->getTasksAsList($tenant->id);

        return Inertia::render('ERP/Tasks/AsList', [
            'arrangedClients' => $arrangedClients,
        ]);
    }

    public function show(ERPTask $task)
    {
        $this->authorize('view', $task);
        $tenant = $this->resolveTenant();

        $task->load([
            'client',
            'project',
            'creator',
            'comments' => fn($q) => $q->orderBy('created_at', 'asc')->with('commenter'),
            'items' => fn($q) => $q->whereNull('parent_id')
                ->with(['children' => fn($q) => $q->orderBy('sort_index')->orderBy('id')])
                ->orderBy('completed')
                ->orderBy('sort_index')
                ->orderBy('id', 'desc'),
        ]);

        return Inertia::render('ERP/Tasks/Show', [
            'task'  => TaskResource::make($task)->resolve(),
            'todos' => TodoItemResource::collection($task->items)->resolve(),
            'comments' => $task->comments,
            'completion' => $task->completionPercentage(),
            'currencies' => \App\Models\Currency::all(),
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $this->authorize('create', ERPTask::class);
        $tenant = $this->resolveTenant();

        $task = $this->taskService->createTask($request->validated(), $tenant);

        if ($request->input('quick_add') || $request->ajax() || $request->has('redirect_back')) {
            return redirect()->back()->with('success', __('general.task_created'));
        }

        return redirect()->route('erp.tasks.show', $task->id)
            ->with('success', __('general.task_created'));
    }

    public function update(UpdateTaskRequest $request, ERPTask $task)
    {
        $this->authorize('update', $task);
        $tenant = $this->resolveTenant();

        $this->taskService->updateTask($task, $request->validated(), $tenant);

        return back()->with('success', __('general.task_updated'));
    }

    public function archive(ERPTask $task)
    {
        $this->authorize('update', $task);

        $this->taskService->archiveTask($task);
        return back()->with('success', __('general.task_archived'));
    }

    public function unarchive(ERPTask $task)
    {
        $this->authorize('update', $task);

        $this->taskService->unarchiveTask($task);
        return back()->with('success', __('general.task_restored'));
    }

    public function destroy(ERPTask $task)
    {
        $this->authorize('delete', $task);

        $this->taskService->deleteTask($task);
        return redirect()->route('erp.tasks.index')->with('success', __('general.task_deleted'));
    }

    // ── Todo Item CRUD ───────────────────────────────────────────────

    /**
     * Add a todo item to a task.
     * Recovered from old project: Admin/TodoController::store()
     */
    public function storeItem(Request $request, ERPTask $task)
    {
        $this->authorize('update', $task);

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

        $item = $this->taskService->storeItem($task, $validated);

        return response()->json([
            'success' => true,
            'item'    => TodoItemResource::make($item)->resolve(),
        ]);
    }

    /**
     * Update a todo item inline.
     * Recovered from old project: set_title, set_priority, set_description actions.
     */
    public function updateItem(Request $request, ERPTask $task, ERPTodoItem $item)
    {
        $this->authorize('update', $task);
        if ($item->task_id !== $task->id) {
            abort(403, __('general.unauthorized_access'));
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

        $item = $this->taskService->updateItem($item, $validated);

        return response()->json(['success' => true, 'item' => TodoItemResource::make($item)->resolve()]);
    }

    /**
     * Toggle complete/incomplete on a todo item.
     * Recovered from old project: Admin/TodoController::complete()
     */
    public function completeItem(Request $request, ERPTask $task, ERPTodoItem $item)
    {
        $this->authorize('update', $task);
        if ($item->task_id !== $task->id) {
            abort(403, __('general.unauthorized_access'));
        }

        $item = $this->taskService->completeItem($item, $request->boolean('completed'));

        return response()->json(['success' => true, 'item' => TodoItemResource::make($item)->resolve()]);
    }

    /**
     * Drag-and-drop reorder todo items.
     * Recovered from old project: Admin/TodoController::sort()
     */
    public function sortItems(Request $request, ERPTask $task)
    {
        $this->authorize('update', $task);

        $request->validate(['sort' => 'required|array']);

        $this->taskService->sortItems($task, $request->input('sort'));

        return response()->json(['success' => true]);
    }

    /**
     * Pause a todo item.
     * Recovered from old project: Admin/TodoController::pause()
     */
    public function pauseItem(ERPTask $task, ERPTodoItem $item)
    {
        $this->authorize('update', $task);
        if ($item->task_id !== $task->id) {
            abort(403, __('general.unauthorized_access'));
        }

        $this->taskService->pauseItem($item);
        return response()->json(['success' => true]);
    }

    /**
     * Resume a paused todo item.
     * Recovered from old project: Admin/TodoController::resume()
     */
    public function resumeItem(ERPTask $task, ERPTodoItem $item)
    {
        $this->authorize('update', $task);
        if ($item->task_id !== $task->id) {
            abort(403, __('general.unauthorized_access'));
        }

        $this->taskService->resumeItem($item);
        return response()->json(['success' => true]);
    }

    /**
     * Delete a todo item — guards against deleting paid items.
     * Recovered from old project: Admin/TodoController::delete()
     */
    public function destroyItem(ERPTask $task, ERPTodoItem $item)
    {
        $this->authorize('update', $task);
        if ($item->task_id !== $task->id) {
            abort(403, __('general.unauthorized_access'));
        }

        try {
            $this->taskService->destroyItem($item);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    // ── Comments ──────────────────────────────────────────────────

    public function storeComment(Request $request, ERPTask $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'comment' => 'required|string',
        ]);

        $user = Auth::guard('erp_team')->user() ?? Auth::user();

        $task->comments()->create([
            'commenter_id'   => $user->id,
            'commenter_type' => get_class($user),
            'comment'        => $validated['comment'],
            'approved'       => true,
        ]);

        return back()->with('success', __('general.comment_added'));
    }

    public function destroyComment(ERPTask $task, \App\Models\Comment $comment)
    {
        $this->authorize('update', $task);

        $user = Auth::guard('erp_team')->user() ?? Auth::user();
        
        // Ensure user can only delete their own comments or admins can delete any
        if ($comment->commenter_id !== $user->id && !($user instanceof \App\Models\User)) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', __('general.comment_deleted'));
    }


}
