<?php

namespace App\Modules\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingSmartSlotRule extends Model
{
    use SoftDeletes;

    protected $table = 'booking_smart_slot_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'target_metric',
        'conditions',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];
}
