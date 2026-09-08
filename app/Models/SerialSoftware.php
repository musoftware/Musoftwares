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

    public const PRICING_FREE = 'free';

    public const PRICING_SINGLE = 'single';

    public const PRICING_PACKAGES = 'packages';

    public const CYCLE_LIFETIME = 'lifetime';

    public const CYCLE_MONTHLY = 'monthly';

    public const CYCLE_ANNUAL = 'annual';

    public const CYCLE_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'is_active',
        'default_status',
        'pricing_type',
        'requires_payment',
        'price',
        'reseller_price',
        'currency',
        'billing_cycle',
        'billing_days',
        'whatsapp_number',
        'payment_instructions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_payment' => 'boolean',
        'price' => 'decimal:2',
        'reseller_price' => 'decimal:2',
        'billing_days' => 'integer',
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

    public function isFree(): bool
    {
        return $this->pricing_type === self::PRICING_FREE;
    }

    public function isSinglePaid(): bool
    {
        return $this->pricing_type === self::PRICING_SINGLE;
    }

    public function hasPackages(): bool
    {
        return $this->pricing_type === self::PRICING_PACKAGES;
    }

    /**
     * @return HasMany<SerialSoftwarePackage>
     */
    public function packages(): HasMany
    {
        return $this->hasMany(SerialSoftwarePackage::class, 'serial_software_id')->orderBy('sort_order');
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

    /**
     * @return HasMany<SerialDeviceTrialLog>
     */
    public function trialLogs(): HasMany
    {
        return $this->hasMany(SerialDeviceTrialLog::class, 'serial_software_id');
    }
}
