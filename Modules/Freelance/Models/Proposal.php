<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proposal extends Model
{
    use HasFactory;
    
    protected static function newFactory()
    {
        return \Modules\Freelance\Database\Factories\ProposalFactory::new();
    }

    protected $table = 'freelance_proposals';
    protected $fillable = ['job_id', 'freelancer_id', 'cover_letter', 'bid_amount', 'currency_id', 'proposed_budget_points', 'points_spent', 'status'];

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
