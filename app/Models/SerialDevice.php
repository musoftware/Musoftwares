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
        'package_id',
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
     * @return BelongsTo<SerialSoftwarePackage, self>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(SerialSoftwarePackage::class, 'package_id');
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
     * Get resolved custom keys combining software defaults, package overrides, and device overrides.
     *
     * @return array<string, string>
     */
    public function getResolvedCustomKeys(): array
    {
        $softwareKeys = SerialSoftwareKey::where('serial_software_id', $this->serial_software_id)->get();
        if ($softwareKeys->isEmpty()) {
            return [];
        }

        // Determine assigned package if any
        $packageId = $this->package_id;
        if (! $packageId && $this->userDeviceAssignment) {
            $packageId = $this->userDeviceAssignment->package_id;
        }
        if (! $packageId && $this->userDeviceAssignment?->user_id) {
            $license = SerialSoftwareLicense::where('user_id', $this->userDeviceAssignment->user_id)
                ->where('serial_software_id', $this->serial_software_id)
                ->active()
                ->first();
            $packageId = $license?->package_id;
        }

        $packageCustomValues = [];
        if ($packageId) {
            $pkg = SerialSoftwarePackage::find($packageId);
            if ($pkg && is_array($pkg->custom_values)) {
                $packageCustomValues = $pkg->custom_values;
            }
        }

        $deviceOverrides = SerialDeviceKey::where('serial_device_id', $this->id)
            ->pluck('value', 'serial_software_key_id')
            ->toArray();

        $resolved = [];
        foreach ($softwareKeys as $swKey) {
            // 1. Software default
            $val = (string) ($swKey->default_value ?? '');

            // 2. Package override
            if (array_key_exists($swKey->key, $packageCustomValues) && $packageCustomValues[$swKey->key] !== null && $packageCustomValues[$swKey->key] !== '') {
                $val = (string) $packageCustomValues[$swKey->key];
            }

            // 3. Device specific override
            if (isset($deviceOverrides[$swKey->id])) {
                $val = (string) $deviceOverrides[$swKey->id];
            }

            $resolved[$swKey->key] = $val;
        }

        return $resolved;
    }
}
