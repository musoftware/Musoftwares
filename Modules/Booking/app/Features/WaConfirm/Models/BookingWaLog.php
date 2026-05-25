<?php

namespace Modules\Booking\app\Features\WaConfirm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingWaLog extends Model
{
    protected $table = 'booking_wa_logs';

    protected $fillable = [
        'tenant_id',
        'confirmation_id',
        'event_type',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
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

    public function confirmation(): BelongsTo
    {
        return $this->belongsTo(BookingWaConfirmation::class, 'confirmation_id');
    }
}
