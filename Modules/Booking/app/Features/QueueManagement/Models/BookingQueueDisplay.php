<?php

namespace Modules\Booking\app\Features\QueueManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingQueueDisplay extends Model
{
    protected $table = 'booking_queue_displays';

    protected $fillable = [
        'tenant_id',
        'queue_id',
        'tv_name',
        'display_key',
        'theme_settings',
        'is_active',
    ];

    protected $casts = [
        'theme_settings' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where('tenant_id', app('currentTenant')->id);
            } elseif (auth()->check() && (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id())) {
                $builder->where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()));
            }
        });
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(BookingQueue::class, 'queue_id');
    }
}
