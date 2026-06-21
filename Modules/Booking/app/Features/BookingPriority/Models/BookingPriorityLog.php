<?php

namespace Modules\Booking\app\Features\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingPriorityLog extends Model
{
    use SoftDeletes;

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
