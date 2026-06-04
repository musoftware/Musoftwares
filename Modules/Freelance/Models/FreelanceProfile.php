<?php

namespace Modules\Freelance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FreelanceProfile extends Model
{
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
}
