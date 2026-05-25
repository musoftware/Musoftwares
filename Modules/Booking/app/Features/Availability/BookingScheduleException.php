<?php

namespace Modules\Booking\Features\Availability;

use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Features\Resources\BookingResource;

class BookingScheduleException extends Model
{
    protected $table = 'booking_schedule_exceptions';

    protected $guarded = ['id'];

    public function resource()
    {
        return $this->belongsTo(BookingResource::class, 'resource_id');
    }
}
