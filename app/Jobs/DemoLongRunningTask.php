<?php

namespace App\Jobs;

class DemoLongRunningTask extends BaseBackgroundTask
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->markAsProcessing();

        // Simulate some work
        $totalSteps = $this->payload['steps'] ?? 5;
        
        for ($i = 1; $i <= $totalSteps; $i++) {
            sleep(1); // Simulate time-consuming operation
            
            $progress = (int)(($i / $totalSteps) * 100);
            $this->updateProgress($progress, [
                'step' => $i,
                'message' => "Completed step $i of $totalSteps"
            ]);
        }

        $this->markAsCompleted([
            'success' => true,
            'final_message' => 'Task finished successfully!'
        ]);
    }
}
