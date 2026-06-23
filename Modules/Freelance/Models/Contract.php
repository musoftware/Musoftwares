<?php

namespace Modules\Freelance\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class Contract extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'freelance_contracts';
    protected $fillable = ['job_id', 'proposal_id', 'client_id', 'freelancer_id', 'amount', 'currency_id', 'contract_points', 'status', 'started_at', 'completed_at'];

    protected $appends = [];



    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function newFactory()
    {
        return \Modules\Freelance\Database\Factories\ContractFactory::new();
    }

    public function job(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function proposal(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function freelancer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
