<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Contract extends Model
{
    protected $table = 'freelance_contracts';
    protected $fillable = ['job_id', 'proposal_id', 'client_id', 'freelancer_id', 'amount', 'currency_id', 'contract_points', 'status', 'started_at', 'completed_at'];

    protected $appends = ['formatted_amount'];

    public function getFormattedAmountAttribute()
    {
        if ($this->amount && $this->currency) {
            return sprintf($this->currency->string_format, $this->amount);
        }
        return $this->amount;
    }

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
