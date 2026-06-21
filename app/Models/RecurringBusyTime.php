<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringBusyTime extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'is_recurring',
        'day_of_week',
        'specific_date',
        'is_full_day',
        'start_time',
        'end_time',
        'reason',
        'is_active',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'is_full_day' => 'boolean',
        'is_active' => 'boolean',
        'specific_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
