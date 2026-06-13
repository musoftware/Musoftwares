<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Spatie\ModelStates\HasStates;
use Modules\Freelance\Domains\Job\States\JobState;

class Job extends Model
{
    use Searchable, SoftDeletes, HasStates, HasFactory;

    protected $table = 'freelance_jobs';

    protected $fillable = ['client_id', 'title', 'description', 'budget', 'currency_id', 'min_proposal_points', 'type', 'service_type', 'country', 'city', 'district', 'latitude', 'longitude', 'duration', 'status', 'last_poked_at'];

    protected $appends = [];

    protected $casts = [
        'status' => JobState::class,
        'last_poked_at' => 'datetime',
    ];



    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function proposals(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function contracts(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function skills(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'freelance_job_skills')
            ->withPivot('is_required')
            ->withTimestamps();
    }

    public function currency(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    protected static function newFactory()
    {
        return \Modules\Freelance\Database\Factories\JobFactory::new();
    }
}
