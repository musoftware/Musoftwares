<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTask extends Model
{
    use HasFactory;

    protected $table = 'jobs_and_tasks';

    protected $fillable = [
        'user_id',
        'title',
        'mission',
        'description',
        'notice',
        'points',
        'user_limit',
        'required_rank',
        'points_balance',
        'completion_policy'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userJobs()
    {
        return $this->hasMany(JobTaskUser::class, 'job_id');
    }

    // Many-to-many relationship with users
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_jobs', 'job_id', 'user_id')->withTimestamps();
    }

    public function feedbacks()
    {
        return $this->hasManyThrough(
            JobTaskFeedback::class, // Final target model
            JobTaskUser::class,     // Intermediate model
            'job_id',               // Foreign key on intermediate model (`user_jobs`)
            'user_job_id',          // Foreign key on target model (`job_feedback`)
            'id',                   // Local key on current model (`jobs_and_tasks`)
            'id'                    // Local key on intermediate model (`user_jobs`)
        );
    }

    // Check if the job has reached its user limit
    public function isFull()
    {
        return $this->userJobs()->count() >= $this->user_limit;
    }

    public function checkEnrolled($user)
    {
        return $this->userJobs()->where('user_id', $user->id)->exists();
    }

    // Check if a user can complete the job again
    public function canCompleteAgain($user)
    {
        if ($this->completion_policy === 'multiple') {
            return true; // Users can complete the job multiple times
        }

        // For "once" policy, check if the user has already completed the job
        return !$this->userJobs()->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'approved'])->exists();
    }
}
