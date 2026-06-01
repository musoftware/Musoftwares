<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoldMarketSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'driver',
        'market_key',
        'base_currency',
        'endpoint_url',
        'credentials',
        'priority',
        'is_active',
        'is_healthy',
        'last_success_at',
        'last_failure_at',
        'failure_count',
        'uptime_pct',
        'avg_latency_ms',
        'validation_threshold_pct',
    ];

    protected $casts = [
        'credentials'         => 'encrypted:array',
        'is_active'           => 'boolean',
        'is_healthy'          => 'boolean',
        'last_success_at'     => 'datetime',
        'last_failure_at'     => 'datetime',
        'uptime_pct'          => 'decimal:2',
        'validation_threshold_pct' => 'decimal:2',
    ];

    protected $hidden = ['credentials'];

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('is_healthy', true);
    }

    public function scopeForMarket($query, string $marketKey)
    {
        return $query->where('market_key', $marketKey);
    }

    public function scopeForTenantOrGlobal($query, int $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
        });
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function livePrice()
    {
        return $this->hasOne(GoldLivePrice::class, 'source_id');
    }

    public function snapshots()
    {
        return $this->hasMany(GoldPriceSnapshot::class, 'source_id');
    }

    public function priceEvents()
    {
        return $this->hasMany(GoldPriceEvent::class, 'source_id');
    }

    // ─── Business Logic ─────────────────────────────────────────────────────────

    public function markHealthy(int $latencyMs): void
    {
        $this->update([
            'is_healthy'      => true,
            'failure_count'   => 0,
            'last_success_at' => now(),
            'avg_latency_ms'  => $latencyMs,
        ]);
    }

    public function markFailed(): void
    {
        $newCount = $this->failure_count + 1;
        $this->update([
            'failure_count'   => $newCount,
            'last_failure_at' => now(),
            'is_healthy'      => $newCount < 5, // quarantine after 5 failures
        ]);
    }

    public function isQuarantined(): bool
    {
        return !$this->is_healthy;
    }

    protected static function newFactory()
    {
        return \Database\Factories\GoldMarketSourceFactory::new();
    }
}
