<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Proposal extends Model
{
    protected $table = 'freelance_proposals';
    protected $fillable = ['job_id', 'freelancer_id', 'cover_letter', 'proposed_budget_points', 'points_spent', 'status'];

    protected $appends = [];

    // Removed formatted_bid_amount since we are using points now

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    // Currency relation removed
}
