<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialSoftwareReseller extends Model
{
    use HasFactory;

    protected $table = 'serial_software_resellers';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'user_id',
        'serial_software_id',
        'max_devices',
        'status',
        'notes',
    ];

    protected $casts = [
        'max_devices' => 'integer',
    ];

    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_SUSPENDED,
        ];
    }

    /**
     * Reseller user account.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Allocated serial software.
     */
    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    /**
     * Whether this reseller has an unlimited device quota for this software.
     */
    public function isUnlimitedDevices(): bool
    {
        return $this->max_devices === null;
    }

    /**
     * Count of active devices currently assigned by this reseller for this software.
     */
    public function activeDevicesCount(): int
    {
        return SerialUserDevice::where('reseller_id', $this->user_id)
            ->where('status', SerialUserDevice::STATUS_ACTIVE)
            ->whereHas('devices', function ($q) {
                $q->where('serial_software_id', $this->serial_software_id);
            })
            ->count();
    }

    /**
     * Remaining devices quota for this reseller. Returns null if unlimited.
     */
    public function remainingQuota(): ?int
    {
        if ($this->isUnlimitedDevices()) {
            return null;
        }

        return max(0, $this->max_devices - $this->activeDevicesCount());
    }
}
