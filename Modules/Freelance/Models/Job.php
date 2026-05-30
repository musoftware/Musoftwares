<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Spatie\ModelStates\HasStates;
use Modules\Freelance\Domains\Job\States\JobState;

class Job extends Model
{
    use Searchable, SoftDeletes, HasStates;

    protected $table = 'freelance_jobs';

    protected $fillable = ['client_id', 'title', 'description', 'budget', 'currency_id', 'min_proposal_points', 'type', 'duration', 'status'];

    protected $appends = ['formatted_budget'];

    protected $casts = [
        'status' => JobState::class,
    ];

    public function getFormattedBudgetAttribute()
    {
        if ($this->budget && $this->currency) {
            return sprintf($this->currency->string_format, $this->budget);
        }
        return $this->budget;
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'freelance_job_skills')
            ->withPivot('is_required')
            ->withTimestamps();
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
