<?php

namespace App\Services;

use App\Models\BackgroundTask;
use Illuminate\Support\Facades\Log;

class BackgroundTaskService extends BaseService
{
    /**
     * Dispatch a queued background task and record it in the database.
     *
     * @param  int  $userId  The ID of the user requesting the task
     * @param  string  $type  The type/name of the task
     * @param  string  $jobClass  The fully qualified class name of the job (must extend BaseBackgroundTask)
     * @param  array  $payload  Additional data to pass
     * @param  string|null  $queue  Specific queue to dispatch to
     */
    public static function dispatch(int $userId, string $type, string $jobClass, array $payload = [], ?string $queue = null): BackgroundTask
    {
        // 1. Create the task record
        $task = BackgroundTask::create([
            'user_id' => $userId,
            'type' => $type,
            'status' => 'pending',
            'progress' => 0,
            'payload' => $payload,
        ]);

        try {
            // 2. Dispatch the job, passing the task ID
            $job = new $jobClass($task->id, $payload);

            if ($queue) {
                dispatch($job)->onQueue($queue);
            } else {
                dispatch($job);
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch background task: '.$e->getMessage());
            $task->update([
                'status' => 'failed',
                'error' => 'Failed to queue the task.',
                'completed_at' => now(),
            ]);
        }

        return $task;
    }
}
