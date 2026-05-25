<?php

namespace Modules\Booking\app\Features\WaConfirm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Booking\Models\Booking;

class BookingWaConfirmation extends Model
{
    protected $table = 'booking_wa_confirmations';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'status',
        'expires_at',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(BookingWaActionToken::class, 'confirmation_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingWaLog::class, 'confirmation_id');
    }
}
