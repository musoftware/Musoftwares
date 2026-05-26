<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTaskFeedback extends Model
{
    use HasFactory;

    protected $table = 'job_feedback';

    protected $fillable = ['user_job_id', 'content', 'reject_reason'];

    public function userJob()
    {
        return $this->belongsTo(JobTaskUser::class, 'user_job_id');
    }
}
