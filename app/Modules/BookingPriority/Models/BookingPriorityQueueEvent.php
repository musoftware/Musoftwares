<?php

namespace App\Modules\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPriorityQueueEvent extends Model
{
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
