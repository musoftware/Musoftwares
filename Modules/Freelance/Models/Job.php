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

    protected $fillable = ['client_id', 'title', 'description', 'budget_points', 'min_proposal_points', 'type', 'duration', 'status'];

    protected $appends = [];

    protected $casts = [
        'status' => JobState::class,
    ];

    // Removed formatted_budget since we are using points now

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

    // Currency relation removed
}
