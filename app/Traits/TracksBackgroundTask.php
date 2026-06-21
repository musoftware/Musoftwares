<?php

namespace App\Traits;

use App\Models\BackgroundTask;
use App\Events\BackgroundTaskUpdated;

trait TracksBackgroundTask
{
    protected ?BackgroundTask $backgroundTask = null;

    /**
     * Initialize the background task.
     */
    protected function initBackgroundTask(int $userId, string $type, array $payload = [])
    {
        $this->backgroundTask = BackgroundTask::create([
            'user_id' => $userId,
            'type' => $type,
            'status' => 'pending',
            'progress' => 0,
            'payload' => $payload,
        ]);

        broadcast(new BackgroundTaskUpdated($this->backgroundTask));
        
        return $this->backgroundTask;
    }

    /**
     * Set the task as processing.
     */
    protected function markTaskAsProcessing()
    {
        if ($this->backgroundTask) {
            $this->backgroundTask->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);
            broadcast(new BackgroundTaskUpdated($this->backgroundTask));
        }
    }

    /**
     * Update the progress of the task.
     */
    protected function updateTaskProgress(int $progress, array $partialResult = [])
    {
        if ($this->backgroundTask) {
            $updateData = ['progress' => $progress];
            
            if (!empty($partialResult)) {
                $currentResult = $this->backgroundTask->result ?? [];
                $updateData['result'] = array_merge($currentResult, $partialResult);
            }

            $this->backgroundTask->update($updateData);
            broadcast(new BackgroundTaskUpdated($this->backgroundTask));
        }
    }

    /**
     * Mark the task as completed.
     */
    protected function markTaskAsCompleted(array $result = [])
    {
        if ($this->backgroundTask) {
            $this->backgroundTask->update([
                'status' => 'completed',
                'progress' => 100,
                'result' => $result,
                'completed_at' => now(),
            ]);
            broadcast(new BackgroundTaskUpdated($this->backgroundTask));
        }
    }

    /**
     * Mark the task as failed.
     */
    protected function markTaskAsFailed(string $error)
    {
        if ($this->backgroundTask) {
            $this->backgroundTask->update([
                'status' => 'failed',
                'error' => $error,
                'completed_at' => now(),
            ]);
            broadcast(new BackgroundTaskUpdated($this->backgroundTask));
        }
    }
}
