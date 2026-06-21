<?php

namespace Modules\Booking\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Booking\Database\Factories\BookingBlockedDateFactory;

class BookingBlockedDate extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'booking_provider_id',
        'starts_at',
        'ends_at',
        'reason',
        'is_recurring',
        'recurring_pattern',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_recurring' => 'boolean',
    ];

    public function provider()
    {
        return $this->belongsTo(BookingProvider::class, 'booking_provider_id');
    }

    // protected static function newFactory(): BookingBlockedDateFactory
    // {
    //     // return BookingBlockedDateFactory::new();
    // }
}
