<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerialDeviceTrialLog extends Model
{
    use HasFactory;

    protected $table = 'serial_device_trial_logs';

    protected $fillable = [
        'serial_software_id',
        'device_id',
        'reseller_id',
        'customer_user_id',
        'granted_at',
    ];

    protected $casts = [
        'granted_at' => 'datetime',
    ];

    /**
     * Check if a specific device has ever consumed a free trial for a specific software.
     */
    public static function hasUsedTrial(int $softwareId, string $deviceId): bool
    {
        return static::where('serial_software_id', $softwareId)
            ->where('device_id', trim($deviceId))
            ->exists();
    }

    /**
     * Record a free trial grant for this device and software.
     */
    public static function recordTrial(int $softwareId, string $deviceId, ?int $resellerId = null, ?int $customerId = null): self
    {
        return static::create([
            'serial_software_id' => $softwareId,
            'device_id' => trim($deviceId),
            'reseller_id' => $resellerId,
            'customer_user_id' => $customerId,
            'granted_at' => now()->setTimezone('Africa/Cairo'),
        ]);
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(SerialSoftware::class, 'serial_software_id');
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_user_id');
    }
}
