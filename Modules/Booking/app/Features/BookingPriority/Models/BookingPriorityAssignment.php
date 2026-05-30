<?php

namespace Modules\Booking\app\Features\BookingPriority\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BookingPriorityAssignment extends Model
{
    protected $table = 'booking_priority_assignments';

    protected $fillable = [
        'tenant_id',
        'model_type',
        'model_id',
        'priority_level_id',
        'reason',
        'assigned_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(BookingPriorityLevel::class, 'priority_level_id');
    }
}
