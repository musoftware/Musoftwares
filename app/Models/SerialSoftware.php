<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A software/program that uses the serial license system.
 * Auto-created by SerialDevice.register() API when a new program_name is seen.
 *
 * default_status is applied to new devices on their first check-in.
 * Changing default_status affects NEW devices only; existing ones are unaffected
 * unless updated explicitly via admin panel.
 */
class SerialSoftware extends Model
{
    use HasFactory;

    protected $table = 'serial_softwares';

    public const DEFAULT_STATUS_ACTIVE   = 'active';
    public const DEFAULT_STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'default_status',
    ];

    public static function statuses(): array
    {
        return [
            self::DEFAULT_STATUS_ACTIVE,
            self::DEFAULT_STATUS_INACTIVE,
        ];
    }

    public function devices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SerialDevice::class, 'serial_software_id');
    }
}
