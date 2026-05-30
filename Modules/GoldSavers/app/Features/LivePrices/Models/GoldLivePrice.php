<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoldLivePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'source_id',
        'market_key',
        'price_usd_oz',
        'price_gram_24k',
        'price_gram_21k',
        'price_gram_18k',
        'price_gram_14k',
        'price_gram_custom',
        'custom_purity',
        'buy_price',
        'sell_price',
        'spread',
        'currency_id',
        'exchange_rate',
        'price_delta',
        'price_delta_pct',
        'direction',
        'provider_latency_ms',
        'is_stale',
        'stale_since',
        'fetched_at',
        'broadcasted_at',
    ];

    protected $casts = [
        'price_usd_oz'          => 'decimal:4',
        'price_gram_24k'    => 'decimal:4',
        'price_gram_21k'    => 'decimal:4',
        'price_gram_18k'    => 'decimal:4',
        'price_gram_14k'    => 'decimal:4',
        'price_gram_custom' => 'decimal:4',
        'custom_purity'         => 'decimal:4',
        'buy_price'             => 'decimal:4',
        'sell_price'            => 'decimal:4',
        'spread'                => 'decimal:4',
        'exchange_rate'         => 'decimal:6',
        'price_delta'           => 'decimal:4',
        'price_delta_pct'       => 'decimal:4',
        'is_stale'              => 'boolean',
        'stale_since'           => 'datetime',
        'fetched_at'            => 'datetime',
        'broadcasted_at'        => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function source()
    {
        return $this->belongsTo(GoldMarketSource::class, 'source_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeFresh($query)
    {
        return $query->where('is_stale', false);
    }

    // ─── Computed ───────────────────────────────────────────────────────────────

    /**
     * Get the gram price for a specific karat.
     */
    public function gramPriceForKarat(int $karat): float
    {
        return match ($karat) {
            24      => (float) $this->price_gram_24k,
            21      => (float) $this->price_gram_21k,
            18      => (float) $this->price_gram_18k,
            14      => (float) $this->price_gram_14k,
            default => (float) $this->price_gram_custom,
        };
    }

    /**
     * Mark this price as stale.
     */
    public function markStale(): void
    {
        if (!$this->is_stale) {
            $this->update(['is_stale' => true, 'stale_since' => now()]);
        }
    }

    /**
     * Mark as fresh after recovery.
     */
    public function markFresh(): void
    {
        $this->update(['is_stale' => false, 'stale_since' => null]);
    }
}
