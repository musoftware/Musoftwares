<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialSoftware extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'serial_softwares';

    public const DEFAULT_STATUS_ACTIVE = 'active';

    public const DEFAULT_STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'default_status',
        'requires_payment',
        'price',
        'currency',
        'whatsapp_number',
        'payment_instructions',
    ];

    protected $casts = [
        'requires_payment' => 'boolean',
        'price' => 'decimal:2',
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

    /**
     * @return HasMany<SerialSoftwareReseller>
     */
    public function resellers(): HasMany
    {
        return $this->hasMany(SerialSoftwareReseller::class, 'serial_software_id');
    }

    /**
     * @return HasMany<SerialSoftwareKey>
     */
    public function customKeys(): HasMany
    {
        return $this->hasMany(SerialSoftwareKey::class, 'serial_software_id');
    }

    /**
     * @return HasMany<SerialSoftwareLicense>
     */
    public function licenses(): HasMany
    {
        return $this->hasMany(SerialSoftwareLicense::class, 'serial_software_id');
    }

    /**
     * @return HasMany<StoreTool>
     */
    public function storeTools(): HasMany
    {
        return $this->hasMany(StoreTool::class, 'serial_software_id');
    }
}
