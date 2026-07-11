<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialDevice extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_BLOCKED = 'blocked';

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
    ];

    /**
     * @return string[]
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_INACTIVE,
            self::STATUS_BLOCKED,
        ];
    }

    /**
     * @return BelongsTo<SerialSoftware, self>
     */
    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    /**
     * Get the user device assignment for this device.
     */
    public function userDeviceAssignment()
    {
        return $this->hasOne(SerialUserDevice::class, 'device_id', 'device_id');
    }
}
