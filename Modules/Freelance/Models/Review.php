<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Review extends Model
{
    protected $table = 'freelance_reviews';
    protected $fillable = ['contract_id', 'reviewer_id', 'reviewee_id', 'rating', 'comment', 'is_visible'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
