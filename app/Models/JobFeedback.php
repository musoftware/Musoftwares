<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobFeedback extends Model
{
    use HasFactory;

    protected $fillable = ['user_job_id', 'content'];

    public function userJob()
    {
        return $this->belongsTo(UserJob::class, 'user_job_id');
    }


}


