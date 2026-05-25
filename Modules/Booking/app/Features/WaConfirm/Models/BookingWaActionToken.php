<?php

namespace Modules\Booking\app\Features\WaConfirm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingWaActionToken extends Model
{
    protected $table = 'booking_wa_action_tokens';

    protected $fillable = [
        'tenant_id',
        'confirmation_id',
        'token_hash',
        'action_type',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
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

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }
}
