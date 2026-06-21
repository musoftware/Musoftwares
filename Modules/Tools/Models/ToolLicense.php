<?php

namespace Modules\Tools\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolLicense extends Model
{
    use SoftDeletes;

    protected $table = 'tool_licenses';

    protected $fillable = [
        'license_key', 'user_id', 'tool_guid', 'tool_subscription_id',
        'status', 'expires_at', 'last_validated_at',
    ];

    protected $casts = [
        'expires_at'        => 'datetime',
        'last_validated_at' => 'datetime',
    ];

    public static array $statuses = ['active', 'suspended', 'revoked'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(ToolSubscription::class, 'tool_subscription_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(ActivatedDevice::class, 'tool_license_id');
    }

    public function activeDevices(): HasMany
    {
        return $this->hasMany(ActivatedDevice::class, 'tool_license_id')->where('status', 'active');
    }

    public function getToolAttribute()
    {
        $config = config("tools.{$this->tool_guid}");
        if (!$config) {
            return (object) [
                'slug'     => $this->tool_guid,
                'title'    => 'Unknown Tool',
                'icon_url' => null,
                'category' => 'unknown',
            ];
        }

        return (object) [
            'slug'     => $config['slug'] ?? '',
            'title'    => $config['title'] ?? '',
            'icon_url' => $config['icon'] ?? null,
            'category' => $config['category'] ?? '',
        ];
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function canActivateDevice(): bool
    {
        return $this->isValid();
    }

    public function touchValidation(): void
    {
        $this->update(['last_validated_at' => now()]);
    }
}
