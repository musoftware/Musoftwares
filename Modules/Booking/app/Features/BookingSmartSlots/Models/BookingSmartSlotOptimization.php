<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingSmartSlotOptimization extends Model
{
    use SoftDeletes;

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
