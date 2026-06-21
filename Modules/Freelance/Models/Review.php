<?php

namespace Modules\Freelance\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Review extends Model
{
    use SoftDeletes;

    protected $table = 'freelance_reviews';
    protected $fillable = ['contract_id', 'reviewer_id', 'reviewee_id', 'rating', 'comment', 'is_visible'];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function reviewee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewee_id');
    }
}
