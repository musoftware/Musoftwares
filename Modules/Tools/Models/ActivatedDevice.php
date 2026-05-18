<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivatedDevice extends Model
{
    protected $fillable = [
        'tool_license_id', 'user_id', 'hardware_fingerprint',
        'device_name', 'os', 'app_version', 'status',
        'last_seen_at', 'revoked_at', 'ip_address',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    public static array $statuses = ['active', 'revoked', 'banned'];

    public function license(): BelongsTo
    {
        return $this->belongsTo(ToolLicense::class, 'tool_license_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function revoke(): void
    {
        $this->update([
            'status'     => 'revoked',
            'revoked_at' => now(),
        ]);
    }

    public function touchHeartbeat(string $ip, string $version): void
    {
        $this->update([
            'last_seen_at' => now(),
            'ip_address'   => $ip,
            'app_version'  => $version,
        ]);
    }
}
