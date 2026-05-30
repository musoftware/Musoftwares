<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Proposal extends Model
{
    protected $table = 'freelance_proposals';
    protected $fillable = ['job_id', 'freelancer_id', 'cover_letter', 'bid_amount', 'currency_id', 'proposed_budget_points', 'points_spent', 'status'];

    protected $appends = ['formatted_bid_amount'];

    public function getFormattedBidAmountAttribute()
    {
        if ($this->bid_amount && $this->currency) {
            return sprintf($this->currency->string_format, $this->bid_amount);
        }
        return $this->bid_amount;
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
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
