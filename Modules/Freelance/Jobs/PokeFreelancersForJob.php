<?php

namespace Modules\Freelance\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Freelance\Models\Job;
use App\Models\User;
use Modules\Freelance\Notifications\JobReminderNotification;

class PokeFreelancersForJob implements ShouldQueue
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

        $totalNotified = 0;

        // Find users who have ANY of the required skills
        // and have NOT submitted a proposal for this job yet.
        User::whereHas('freelanceSkills', function ($query) use ($requiredSkillIds) {
            $query->whereIn('freelance_skills.id', $requiredSkillIds);
        })->whereNotIn('id', \Modules\Freelance\Models\Proposal::where('job_id', $this->freelanceJob->id)->select('freelancer_id'))
        ->chunk(100, function ($matchingUsers) use (&$totalNotified) {
            // Send a notification to each matching user
            foreach ($matchingUsers as $user) {
                try {
                    $user->notify(new JobReminderNotification($this->freelanceJob));
                    \Log::info("Poke reminder dispatched to user {$user->id} for job {$this->freelanceJob->id}");
                    $totalNotified++;
                } catch (\Exception $e) {
                    \Log::error("Failed to poke user {$user->id} for job {$this->freelanceJob->id}: " . $e->getMessage());
                }
            }
        });

        if ($totalNotified > 0) {
            $this->freelanceJob->increment('notifications_sent_count', $totalNotified);
        }
    }
}
