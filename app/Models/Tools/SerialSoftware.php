<?php

namespace App\Models\Tools;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SerialSoftware extends Model
{
    use HasFactory;

    protected $table = 'serial_softwares';
    public const DEFAULT_STATUS_ACTIVE = 'active';
    public const DEFAULT_STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'default_status',
    ];

    /**
     * @return string[]
     */
    public static function statuses(): array
    {
        return [
            self::DEFAULT_STATUS_ACTIVE,
            self::DEFAULT_STATUS_INACTIVE,
        ];
    }

    /**
     * @return HasMany<SerialDevice>
     */
    public function devices(): HasMany
    {
        return $this->hasMany(SerialDevice::class);
    }
}

