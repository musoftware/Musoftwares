<?php

namespace App\Jobs;

use App\Events\BackgroundTaskUpdated;
use App\Models\BackgroundTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

abstract class BaseBackgroundTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $taskId;

    public array $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(int $taskId, array $payload = [])
    {
        $this->taskId = $taskId;
        $this->payload = $payload;
    }

    /**
     * Get the associated BackgroundTask model.
     */
    protected function getTask(): ?BackgroundTask
    {
        return BackgroundTask::find($this->taskId);
    }

    /**
     * Mark the task as processing.
     */
    protected function markAsProcessing(): void
    {
        if ($task = $this->getTask()) {
            $task->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
            broadcast(new BackgroundTaskUpdated($task));
        }
    }

    /**
     * Update the task progress.
     */
    protected function updateProgress(int $progress, array $partialResult = []): void
    {
        if ($task = $this->getTask()) {
            $updateData = ['progress' => $progress];

            if (! empty($partialResult)) {
                $currentResult = $task->result ?? [];
                $updateData['result'] = array_merge($currentResult, $partialResult);
            }

            $task->update($updateData);
            broadcast(new BackgroundTaskUpdated($task));
        }
    }

    /**
     * Mark the task as completed.
     */
    protected function markAsCompleted(array $result = []): void
    {
        if ($task = $this->getTask()) {
            $task->update([
                'status' => 'completed',
                'progress' => 100,
                'result' => $result,
                'completed_at' => now(),
            ]);
            broadcast(new BackgroundTaskUpdated($task));
        }
    }

    /**
     * Mark the task as failed if the job fails.
     */
    public function failed(Throwable $exception): void
    {
        if ($task = $this->getTask()) {
            $task->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
            broadcast(new BackgroundTaskUpdated($task));
        }
    }
}
