<?php

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingAvailabilityRule extends Model
{
    use HasFactory;

    protected $table = 'booking_availability_rules';

    protected $fillable = [
        'booking_provider_id',
        'booking_event_type_id',
        'type', // 'recurring' or 'one-time'
        'date',
        'weekday', // 0-6 (Sunday-Saturday)
        'start_time',
        'end_time',
        'is_enabled',
    ];

    protected $casts = [
        'date' => 'date',
        'is_enabled' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(BookingProvider::class, 'booking_provider_id');
    }

    public function eventType()
    {
        return $this->belongsTo(BookingEventType::class, 'booking_event_type_id');
    }
}
