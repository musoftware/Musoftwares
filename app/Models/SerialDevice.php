<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * @return HasMany<SerialDeviceKey>
     */
    public function deviceKeys(): HasMany
    {
        return $this->hasMany(SerialDeviceKey::class, 'serial_device_id');
    }

    /**
     * Get resolved custom keys combining software defaults and device overrides.
     *
     * @return array<string, string>
     */
    public function getResolvedCustomKeys(): array
    {
        $softwareKeys = SerialSoftwareKey::where('serial_software_id', $this->serial_software_id)->get();
        if ($softwareKeys->isEmpty()) {
            return [];
        }

        $deviceOverrides = SerialDeviceKey::where('serial_device_id', $this->id)
            ->pluck('value', 'serial_software_key_id')
            ->toArray();

        $resolved = [];
        foreach ($softwareKeys as $swKey) {
            $resolved[$swKey->key] = isset($deviceOverrides[$swKey->id])
                ? (string) $deviceOverrides[$swKey->id]
                : (string) ($swKey->default_value ?? '');
        }

        return $resolved;
    }
}
