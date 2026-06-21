<?php

namespace Modules\PaymentGateway\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GatewayClient extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'gateway_clients';

    protected $fillable = [
        'name',
        'client_id',
        'client_secret',
        'webhook_secret',
        'website',
        'status',
        'allowed_ips',
        'commission_rate',
    ];

    protected $casts = [
        'allowed_ips'     => 'array',
        'commission_rate' => 'decimal:2',
    ];

    protected $hidden = [
        'client_secret',
        'webhook_secret',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GatewayPayment::class, 'client_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // ─── Accessors / Stats ───────────────────────────────────────────────────

    public function getTotalCommissionAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'success')
            ->sum('commission_amount');
    }

    public function getTotalVolumeAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'success')
            ->sum('amount');
    }

    public function getSuccessfulPaymentsCountAttribute(): int
    {
        return $this->payments()->where('status', 'success')->count();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public static function generateClientId(): string
    {
        return 'pgw_' . strtolower(Str::random(20));
    }

    public static function generateSecret(): string
    {
        return 'sk_' . Str::random(40);
    }

    public static function generateWebhookSecret(): string
    {
        return 'whsec_' . Str::random(32);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canAccessFromIp(string $ip): bool
    {
        if (empty($this->allowed_ips)) {
            return true; // No restriction
        }
        return in_array($ip, $this->allowed_ips);
    }
}
