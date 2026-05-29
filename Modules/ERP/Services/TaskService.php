<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\ERPTodoItem;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityService;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\ERP\Transformers\TaskResource;
use Modules\ERP\Transformers\TodoItemResource;

class TaskService
{
    public function createTask(array $validated, Tenant $tenant): ERPTask
    {
        if (!empty($validated['client_id'])) {
            TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        }
        if (!empty($validated['project_id'])) {
            Project::where('tenant_id', $tenant->id)->findOrFail($validated['project_id']);
        }

        $taskName = $validated['task_name'] ?? $validated['title'] ?? 'New Task';

        $task = ERPTask::create(array_merge($validated, [
            'tenant_id'  => $tenant->id,
            'task_name'  => $taskName,
            'created_by' => Auth::id(),
            'status'     => $validated['status'] ?? 'open',
        ]));

        ActivityService::log(
            event: 'task.created',
            description: "Created task board: {$task->task_name}",
            subject: $task,
            workspace: 'erp'
        );

        return $task;
    }

    public function updateTask(ERPTask $task, array $validated, Tenant $tenant): ERPTask
    {
        if (!empty($validated['client_id'])) {
            TenantClient::where('tenant_id', $tenant->id)->findOrFail($validated['client_id']);
        }
        if (!empty($validated['project_id'])) {
            Project::where('tenant_id', $tenant->id)->findOrFail($validated['project_id']);
        }

        $task->update($validated);

        return $task;
    }

    public function archiveTask(ERPTask $task): void
    {
        $task->update(['archived' => true]);
    }

    public function unarchiveTask(ERPTask $task): void
    {
        $task->update(['archived' => false]);
    }

    public function deleteTask(ERPTask $task): void
    {
        $task->delete();
    }

    public function storeItem(ERPTask $task, array $validated): ERPTodoItem
    {
        if (!empty($validated['parent_id'])) {
            ERPTodoItem::where('task_id', $task->id)->findOrFail($validated['parent_id']);
        }

        return ERPTodoItem::create(array_merge($validated, [
            'tenant_id'  => $task->tenant_id,
            'task_id'    => $task->id,
            'sort_index' => ERPTodoItem::where('task_id', $task->id)->max('sort_index') + 1,
        ]));
    }

    public function updateItem(ERPTodoItem $item, array $validated): ERPTodoItem
    {
        $item->update($validated);
        return $item->fresh();
    }

    public function completeItem(ERPTodoItem $item, bool $completed): ERPTodoItem
    {
        if ($completed) {
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

        return $item->fresh();
    }

    public function sortItems(ERPTask $task, array $sortArray): void
    {
        foreach ($sortArray as $index => $itemId) {
            $item = ERPTodoItem::where('task_id', $task->id)->find($itemId);
            if ($item && !$item->completed) {
                $item->update(['sort_index' => $index]);
            }
        }
    }

    public function pauseItem(ERPTodoItem $item): void
    {
        $item->pause();
    }

    public function resumeItem(ERPTodoItem $item): void
    {
        $item->resume();
    }

    public function destroyItem(ERPTodoItem $item): void
    {
        if (!$item->canBeDeleted()) {
            throw new \Exception('Paid items cannot be deleted.');
        }

        $item->delete();
    }

    public function getPaginatedTasks(int $tenantId, array $filters): LengthAwarePaginator
    {
        $query = ERPTask::where('tenant_id', $tenantId)
            ->with(['client', 'project', 'creator'])
            ->withCount(['items', 'items as completed_items_count' => fn($q) => $q->where('completed', true)]);

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (empty($filters['show_archived'])) {
            $query->where('archived', false);
        }

        return $query->latest()->paginate(20);
    }

    public function getTasksAsList(int $tenantId): array
    {
        $todos = ERPTodoItem::query()
            ->where('tenant_id', $tenantId)
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

            $data[$clientId]['tasks'][$taskId]['todos'][] = TodoItemResource::make($todo)->resolve();
        }

        $result = array_values($data);
        foreach ($result as &$clientData) {
            $clientData['tasks'] = array_values($clientData['tasks']);
        }

        return $result;
    }
}
