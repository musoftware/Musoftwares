<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialSoftwarePackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'serial_software_packages';

    public const CYCLE_LIFETIME = 'lifetime';
    public const CYCLE_MONTHLY = 'monthly';
    public const CYCLE_ANNUAL = 'annual';
    public const CYCLE_CUSTOM = 'custom';

    protected $fillable = [
        'serial_software_id',
        'name',
        'price',
        'currency',
        'billing_cycle',
        'billing_days',
        'description',
        'is_active',
        'is_default',
        'sort_order',
        'custom_values',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'billing_days' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
        'custom_values' => 'array',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(SerialDevice::class, 'package_id');
    }

    public function userDevices(): HasMany
    {
        return $this->hasMany(SerialUserDevice::class, 'package_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(SerialSoftwareLicense::class, 'package_id');
    }

    /**
     * Scope query to active packages ordered by sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }
}
