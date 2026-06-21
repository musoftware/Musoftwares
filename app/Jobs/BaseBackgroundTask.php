<?php

namespace App\Jobs;

use App\Models\BackgroundTask;
use App\Events\BackgroundTaskUpdated;
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
     *
     * @param int $taskId
     * @param array $payload
     */
    public function __construct(int $taskId, array $payload = [])
    {
        $this->taskId = $taskId;
        $this->payload = $payload;
    }

    /**
     * Get the associated BackgroundTask model.
     *
     * @return BackgroundTask|null
     */
    protected function getTask(): ?BackgroundTask
    {
        return BackgroundTask::find($this->taskId);
    }

    /**
     * Mark the task as processing.
     *
     * @return void
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
     *
     * @param int $progress
     * @param array $partialResult
     * @return void
     */
    protected function updateProgress(int $progress, array $partialResult = []): void
    {
        if ($task = $this->getTask()) {
            $updateData = ['progress' => $progress];
            
            if (!empty($partialResult)) {
                $currentResult = $task->result ?? [];
                $updateData['result'] = array_merge($currentResult, $partialResult);
            }

            $task->update($updateData);
            broadcast(new BackgroundTaskUpdated($task));
        }
    }

    /**
     * Mark the task as completed.
     *
     * @param array $result
     * @return void
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
     *
     * @param Throwable $exception
     * @return void
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
