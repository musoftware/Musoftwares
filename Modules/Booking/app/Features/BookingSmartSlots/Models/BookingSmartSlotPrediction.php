<?php

namespace Modules\Booking\app\Features\BookingSmartSlots\Models;

use Illuminate\Database\Eloquent\Model;

class BookingSmartSlotPrediction extends Model
{
    protected $table = 'booking_smart_slot_predictions';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'predicted_peak_hour',
        'confidence_score',
    ];

    protected $casts = [
        'predicted_peak_hour' => 'datetime',
        'confidence_score' => 'decimal:2',
    ];
}
