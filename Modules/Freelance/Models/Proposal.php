<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Proposal extends Model
{
    protected $table = 'freelance_proposals';
    protected $fillable = ['job_id', 'freelancer_id', 'cover_letter', 'bid_amount', 'currency_code', 'status'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }
}
