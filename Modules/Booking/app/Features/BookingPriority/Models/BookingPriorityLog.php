<?php

namespace Modules\Booking\app\Features\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;

class BookingPriorityLog extends Model
{
    protected $table = 'booking_priority_logs';

    protected $fillable = [
        'tenant_id',
        'action',
        'description',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
