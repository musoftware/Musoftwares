<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A physical machine/PC that has checked in via the serial API.
 *
 * Lifecycle:
 * 1. Software calls POST /api/serial/device → device auto-created if new
 * 2. status defaults to software's default_status on first creation
 * 3. When admin assigns device to user (SerialUserDevice), Observer syncs status here
 * 4. API response returns this device's status → software enables/disables itself
 *
 * The `status` column here is always a mirror of the linked SerialUserDevice.status.
 */
class SerialDevice extends Model
{
    use HasFactory;

    protected $table = 'serial_devices';

    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'serial_software_id',
        'device_id',
        'status',
        'user_name',
        'user_domain',
        'machine_name',
        'os_version',
        'framework_version',
        'is_64bit_os',
        'is_64bit_process',
        'current_directory',
        'current_culture',
        'current_ui_culture',
        'last_check_date',
    ];

    protected $casts = [
        'last_check_date' => 'datetime',
        'is_64bit_os'     => 'boolean',
        'is_64bit_process'=> 'boolean',
    ];

    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_INACTIVE];
    }

    // ── Relationships ────────────────────────────────────────────────

    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    /**
     * The user assignment for this device (if any).
     * Uses device_id as the join key — device_id is unique across both tables.
     */
    public function userDeviceAssignment(): HasOne
    {
        return $this->hasOne(SerialUserDevice::class, 'device_id', 'device_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isAssigned(): bool
    {
        return $this->userDeviceAssignment()->exists();
    }
}
