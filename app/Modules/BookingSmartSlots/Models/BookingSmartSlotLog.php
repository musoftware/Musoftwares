<?php

namespace App\Modules\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSmartSlotLog extends Model
{
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
