<?php

namespace Modules\Freelance\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JobSkill extends Model
{
    use SoftDeletes;

    protected $table = 'freelance_job_skills';
    protected $fillable = ['job_id', 'skill_id', 'is_required'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
