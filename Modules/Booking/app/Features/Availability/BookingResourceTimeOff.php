<?php

namespace Modules\Booking\Features\Availability;

use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Features\Resources\BookingResource;

class BookingResourceTimeOff extends Model
{
    protected $table = 'booking_resource_time_off';

    protected $guarded = ['id'];

    public function resource()
    {
        return $this->belongsTo(BookingResource::class, 'resource_id');
    }
}
