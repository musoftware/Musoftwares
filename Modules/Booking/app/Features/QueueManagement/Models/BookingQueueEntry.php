<?php

namespace Modules\Booking\app\Features\QueueManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Booking\Models\Booking;

class BookingQueueEntry extends Model
{
    use SoftDeletes;

    protected $table = 'booking_queue_entries';

    protected $fillable = [
        'tenant_id',
        'queue_id',
        'booking_id',
        'walkin_name',
        'walkin_phone',
        'token_number',
        'sequence_number',
        'status',
        'priority_level',
        'wait_time_estimate_minutes',
        'checked_in_at',
        'called_at',
        'serving_at',
        'completed_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'called_at' => 'datetime',
        'serving_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where('tenant_id', app('currentTenant')->id);
            } elseif (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
        
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(BookingQueue::class, 'queue_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingQueueLog::class, 'queue_entry_id');
    }
}
