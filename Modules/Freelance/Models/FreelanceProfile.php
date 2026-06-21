<?php

namespace Modules\Freelance\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FreelanceProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'bio',
        'hourly_rate',
        'receive_job_notifications',
        'notifications_muted_until'
    ];

    protected $casts = [
        'receive_job_notifications' => 'boolean',
        'notifications_muted_until' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAverageRatingAttribute()
    {
        return Review::where('reviewee_id', $this->user_id)
            ->where('is_visible', true)
            ->avg('rating') ?: 0;
    }

    public function getReviewsCountAttribute()
    {
        return Review::where('reviewee_id', $this->user_id)
            ->where('is_visible', true)
            ->count();
    }
}
