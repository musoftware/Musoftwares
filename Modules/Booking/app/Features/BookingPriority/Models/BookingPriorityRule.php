<?php

namespace Modules\Booking\app\Features\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPriorityRule extends Model
{
    protected $table = 'booking_priority_rules';

    protected $fillable = [
        'tenant_id',
        'name',
        'conditions',
        'priority_level_id',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(BookingPriorityLevel::class, 'priority_level_id');
    }
}
