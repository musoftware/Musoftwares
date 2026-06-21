<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingSmartSlotSnapshot extends Model
{
    use SoftDeletes;

    protected $table = 'booking_smart_slot_snapshots';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'resource_id',
        'date',
        'fragmentation_score',
        'utilization_percentage',
    ];

    protected $casts = [
        'date' => 'date',
        'utilization_percentage' => 'decimal:2',
    ];
}
