<?php

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Booking\Database\Factories\BookingEventTypeFactory;

class BookingEventType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'duration_minutes',
        'price',
        'currency',
        'requires_payment',
        'is_active',
        'timezone',
        'buffer_before',
        'buffer_after',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'requires_payment' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
