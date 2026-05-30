<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSmartSlotOptimization extends Model
{
    protected $table = 'booking_smart_slot_optimizations';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'status',
        'metrics_before',
        'metrics_after',
    ];

    protected $casts = [
        'metrics_before' => 'array',
        'metrics_after' => 'array',
    ];
}
