<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingSmartSlotLog extends Model
{
    use SoftDeletes;

    protected $table = 'booking_smart_slot_logs';

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
