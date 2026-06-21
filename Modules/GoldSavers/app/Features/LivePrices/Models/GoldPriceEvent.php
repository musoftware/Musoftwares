<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoldPriceEvent extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'source_id',
        'event_type',
        'event_data',
        'severity',
        'resolved_at',
    ];

    protected $casts = [
        'event_data'  => 'array',
        'resolved_at' => 'datetime',
    ];

    public const TYPES = [
        'price_updated',
        'anomaly_detected',
        'provider_failed',
        'snapshot_generated',
        'stale_detected',
        'broadcast_sent',
        'provider_recovered',
        'manual_override',
    ];

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    // ─── Business Logic ─────────────────────────────────────────────────────────

    public function resolve(): void
    {
        $this->update(['resolved_at' => now()]);
    }

    public static function logEvent(int $tenantId, string $type, array $data = [], string $severity = 'info', ?int $sourceId = null): self
    {
        return static::create([
            'tenant_id'  => $tenantId,
            'source_id'  => $sourceId,
            'event_type' => $type,
            'event_data' => $data,
            'severity'   => $severity,
        ]);
    }
}
