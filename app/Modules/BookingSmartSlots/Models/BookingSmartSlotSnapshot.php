<?php

namespace App\Modules\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSmartSlotSnapshot extends Model
{
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
