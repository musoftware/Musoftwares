<?php

namespace App\Services;

use App\Helpers\FcmHelper;
use App\Mail\AdminTaskCreatedMail;
use App\Mail\ClientTaskReceivedMail;
use App\Models\Project;
use App\Models\Task;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TaskNotificationService
{
    /**
     * Dispatch email and FCM notifications to client and admins when a task is created.
     */
    public static function notifyOnTaskCreated(Task $task, User $client, ?Project $project = null): void
    {
        try {
            $project = $project ?? $task->project;
            $taskTitle = $task->task_name;
            $taskDescription = $task->task_description;
            $priority = $task->priority;
            $dueDate = $task->due_date;

            self::sendNotifications(
                taskTitle: $taskTitle,
                client: $client,
                project: $project,
                taskDescription: $taskDescription,
                priority: $priority,
                dueDate: $dueDate,
                taskId: $task->id
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify on task creation: ' . $e->getMessage());
        }
    }

    /**
     * Dispatch email and FCM notifications to client and admins when a todo item is created on the board.
     */
    public static function notifyOnTodoCreated(Todo $todo, User $client, ?Project $project = null): void
    {
        try {
            $project = $project ?? $todo->project;
            $taskTitle = $todo->title;
            $taskDescription = $todo->description;
            $priority = $todo->priority;
            $dueDate = $todo->inDate ? (string) $todo->inDate : null;

            self::sendNotifications(
                taskTitle: $taskTitle,
                client: $client,
                project: $project,
                taskDescription: $taskDescription,
                priority: $priority,
                dueDate: $dueDate,
                taskId: $todo->id
            );
        } catch (\Throwable $e) {
            Log::error('Failed to notify on todo creation: ' . $e->getMessage());
        }
    }

    private static function sendNotifications(
        string $taskTitle,
        User $client,
        ?Project $project,
        ?string $taskDescription,
        ?string $priority,
        ?string $dueDate,
        int|string $taskId
    ): void {
        $projectName = $project ? ($project->project_name ?? $project->name) : null;
        $projectSuffix = $projectName ? " [مشروع: {$projectName}]" : '';
        $projectId = $project?->id;

        // 1. Send Email to Client ("كلامك وصل للإدارة")
        if (! empty($client->email)) {
            try {
                Mail::to($client->email)->queue(new ClientTaskReceivedMail(
                    taskTitle: $taskTitle,
                    client: $client,
                    project: $project,
                    taskDescription: $taskDescription,
                    priority: $priority,
                    dueDate: $dueDate
                ));
            } catch (\Throwable $e) {
                Log::error("Failed to send task confirmation email to client {$client->id}: " . $e->getMessage());
            }
        }

        // 2. Send FCM to Client
        try {
            $clientTokens = $client->routeNotificationForFcm();
            $clientTokens = is_array($clientTokens) ? array_values(array_filter($clientTokens)) : ($clientTokens ? [$clientTokens] : []);

            if (! empty($clientTokens)) {
                FcmHelper::send_push_notif_to_device(
                    $clientTokens,
                    [
                        'title' => 'تم استلام مهمتك بنجاح',
                        'description' => "كلامك وصل للإدارة! تم استلام مهمتك '{$taskTitle}'{$projectSuffix} وجارٍ متابعتها.",
                        'type' => 'task_created_client',
                        'data_id' => (string) $taskId,
                    ],
                    $projectId ? url("/client/projects/{$projectId}") : url('/client/projects')
                );
            }
        } catch (\Throwable $e) {
            Log::error("Failed to send task FCM to client {$client->id}: " . $e->getMessage());
        }

        // 3. Send Email to all Admins
        try {
            $admins = User::role(['admin', 'super_admin'])->get();
            foreach ($admins as $admin) {
                if ($admin->id === $client->id) {
                    continue;
                }
                if (! empty($admin->email)) {
                    Mail::to($admin->email)->queue(new AdminTaskCreatedMail(
                        taskTitle: $taskTitle,
                        client: $client,
                        project: $project,
                        taskDescription: $taskDescription,
                        priority: $priority,
                        dueDate: $dueDate
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send task notification email to admins: ' . $e->getMessage());
        }

        // 4. Send FCM to all Admins
        try {
            $admins = $admins ?? User::role(['admin', 'super_admin'])->get();
            $adminTokens = [];

            foreach ($admins as $admin) {
                if ($admin->id === $client->id) {
                    continue;
                }
                $tokens = $admin->routeNotificationForFcm();
                if (is_array($tokens)) {
                    $adminTokens = array_merge($adminTokens, $tokens);
                } elseif ($tokens) {
                    $adminTokens[] = $tokens;
                }
            }

            $adminTokens = array_values(array_unique(array_filter($adminTokens)));

            if (! empty($adminTokens)) {
                $clientName = $client->name ?? 'العميل';
                FcmHelper::send_push_notif_to_device(
                    $adminTokens,
                    [
                        'title' => "مهمة جديدة من العميل {$clientName}",
                        'description' => "أضاف العميل مهمة '{$taskTitle}'{$projectSuffix}",
                        'type' => 'task_created_admin',
                        'data_id' => (string) $taskId,
                    ],
                    $projectId ? url("/admin/projects/{$projectId}") : url('/admin/tasks/pending')
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to dispatch FCM notification for task to admins: ' . $e->getMessage());
        }
    }
}
