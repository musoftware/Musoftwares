<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobTaskUser extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_jobs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'job_id',
        'status',
    ];

    /**
     * Get the job associated with this user job.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(JobTask::class, 'job_id', 'id');
    }

    /**
     * Get the user associated with this user job.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get all feedbacks associated with this user job.
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(JobTaskFeedback::class, 'user_job_id', 'id');
    }
}
