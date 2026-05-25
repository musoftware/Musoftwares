<?php

namespace Modules\Booking\Features\Availability;

use Illuminate\Database\Eloquent\Model;
use Modules\Booking\Features\Resources\BookingResource;

class BookingScheduleRule extends Model
{
    protected $table = 'booking_schedule_rules';

    protected $guarded = ['id'];

    protected $casts = [
        'rule_data' => 'array',
    ];

    public function resource()
    {
        return $this->belongsTo(BookingResource::class, 'resource_id');
    }
}
