<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Observers\SerialUserDeviceObserver;

/**
 * Admin assignment: which user owns which device.
 *
 * Business rules:
 * - One device_id can only be assigned to ONE user (unique constraint)
 * - Changing status triggers SerialUserDeviceObserver → syncs to SerialDevice
 * - updateUserStatus() on the controller uses get()->each() to trigger Observer per record
 * - temp_valid_until on the User model allows temporary access override
 */
class SerialUserDevice extends Model
{
    use HasFactory;

    protected $table = 'serial_user_devices';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'device_id',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::observe(SerialUserDeviceObserver::class);
    }

    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The physical device record in serial_devices.
     * Joined by device_id string (not FK — device_id is a natural key).
     */
    public function device(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SerialDevice::class, 'device_id', 'device_id');
    }
}
