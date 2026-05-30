<?php

namespace Modules\Freelance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Freelance\Models\Job;
use App\Models\User;

class NotifyFreelancersForJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $freelanceJob;

    /**
     * Create a new job instance.
     */
    public function __construct(Job $freelanceJob)
    {
        $this->freelanceJob = $freelanceJob;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the required skill IDs for this job
        $requiredSkillIds = $this->freelanceJob->skills()
            ->wherePivot('is_required', true)
            ->pluck('freelance_skills.id')
            ->toArray();

        if (empty($requiredSkillIds)) {
            return;
        }

        // Find users who have ANY of the required skills in chunks
        User::whereHas('freelanceSkills', function ($query) use ($requiredSkillIds) {
            $query->whereIn('freelance_skills.id', $requiredSkillIds);
        })->chunk(100, function ($matchingUsers) {
            // Send a notification to each matching user
            foreach ($matchingUsers as $user) {
                $user->notify(new \Modules\Freelance\Notifications\JobMatchedNotification($this->freelanceJob));
                \Log::info("Notification dispatched to user {$user->id} for job {$this->freelanceJob->id}");
            }
        });
    }
}
