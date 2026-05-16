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

    public $job;

    /**
     * Create a new job instance.
     */
    public function __construct(Job $job)
    {
        $this->job = $job;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the required skill IDs for this job
        $requiredSkillIds = $this->job->skills()
            ->wherePivot('is_required', true)
            ->pluck('freelance_skills.id')
            ->toArray();

        if (empty($requiredSkillIds)) {
            return;
        }

        // Find users who have all the required skills
        $matchingUsers = User::whereHas('freelanceSkills', function ($query) use ($requiredSkillIds) {
            $query->whereIn('freelance_skills.id', $requiredSkillIds);
        }, '=', count($requiredSkillIds))->get();

        // Send a notification to each matching user (simulation)
        foreach ($matchingUsers as $user) {
            // In a real application, you would dispatch a notification here:
            // $user->notify(new JobMatchedNotification($this->job));
            // For now, we will just log it or handle it silently.
            \Log::info("Notification dispatched to user {$user->id} for job {$this->job->id}");
        }
    }
}
