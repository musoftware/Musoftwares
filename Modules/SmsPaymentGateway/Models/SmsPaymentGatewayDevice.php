<?php

namespace Modules\SmsPaymentGateway\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;

class SmsPaymentGatewayDevice extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Modules\SmsPaymentGateway\Database\factories\SmsPaymentGatewayDeviceFactory::new();
    }

    protected $table = 'sms_payment_gateway_devices';
    protected $fillable = [
        'tenant_id',
        'user_id',
        'device_token',
        'device_name',
        'connection_code',
        'connection_code_expires_at',
        'status',
        'enable_spoof_detection',
        'connected_at',
        'last_seen_at',
        'phone_number',
        'sim_slot',
        'sim1_number',
        'sim2_number',
        'metadata',
    ];

    protected $casts = [
        'connection_code_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'enable_spoof_detection' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the device
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this device
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SmsPaymentGatewayTransaction::class, 'device_id');
    }

    /**
     * Check if connection code is valid
     */
    public function isConnectionCodeValid(): bool
    {
        if (!$this->connection_code_expires_at) {
            return false;
        }

        return $this->connection_code_expires_at->isFuture();
    }

    /**
     * Mark device as connected
     */
    public function markAsConnected(): void
    {
        $this->update([
            'status' => 'connected',
            'connected_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
