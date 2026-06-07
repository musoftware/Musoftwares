<?php

namespace Modules\Booking\app\Features\Availability;

use Illuminate\Database\Eloquent\Model;
use Modules\Booking\app\Features\Resources\BookingResource;

class BookingResourceSchedule extends Model
{
    protected $table = 'booking_resource_schedules';

    protected $guarded = ['id'];

    public function resource()
    {
        return $this->belongsTo(BookingResource::class, 'resource_id');
    }
}
