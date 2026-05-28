<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Proposal extends Model
{
    protected $table = 'freelance_proposals';
    protected $fillable = ['job_id', 'freelancer_id', 'cover_letter', 'bid_amount', 'currency_id', 'status'];

    protected $appends = ['formatted_bid_amount'];

    public function getFormattedBidAmountAttribute()
    {
        return \App\Helpers\FinanceHelper::instance()->format_money($this->bid_amount, $this->currency_id);
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
