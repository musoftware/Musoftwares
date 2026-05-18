<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolLicense extends Model
{
    protected $fillable = [
        'license_key', 'user_id', 'tool_id', 'tool_subscription_id',
        'max_devices', 'status', 'expires_at', 'last_validated_at',
    ];

    protected $casts = [
        'expires_at'        => 'datetime',
        'last_validated_at' => 'datetime',
        'max_devices'       => 'integer',
    ];

    public static array $statuses = ['active', 'suspended', 'revoked'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ToolSubscription::class, 'tool_subscription_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(ActivatedDevice::class);
    }

    public function activeDevices(): HasMany
    {
        return $this->hasMany(ActivatedDevice::class)->where('status', 'active');
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function canActivateDevice(): bool
    {
        return $this->isValid()
            && $this->activeDevices()->count() < $this->max_devices;
    }

    public function touchValidation(): void
    {
        $this->update(['last_validated_at' => now()]);
    }
}
