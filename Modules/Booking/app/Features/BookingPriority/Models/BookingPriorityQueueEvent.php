<?php

namespace Modules\Booking\app\Features\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPriorityQueueEvent extends Model
{
    use SoftDeletes;

    protected $table = 'booking_priority_queue_events';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'type',
        'previous_position',
        'new_position',
        'reason',
    ];
}
