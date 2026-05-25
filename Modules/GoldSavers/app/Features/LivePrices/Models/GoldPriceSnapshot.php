<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoldPriceSnapshot extends Model
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
        'buy_price',
        'sell_price',
        'currency_id',
        'exchange_rate',
        'validation_passed',
        'anomaly_detected',
        'anomaly_reason',
        'raw_payload',
        'interval',
        'fetched_at',
    ];

    protected $casts = [
        'validation_passed' => 'boolean',
        'anomaly_detected'  => 'boolean',
        'raw_payload'       => 'array',
        'fetched_at'        => 'datetime',
        'price_usd_oz'      => 'decimal:4',
        'price_gram_24k' => 'decimal:4',
        'price_gram_21k' => 'decimal:4',
        'price_gram_18k' => 'decimal:4',
        'price_gram_14k' => 'decimal:4',
        'buy_price'         => 'decimal:4',
        'sell_price'        => 'decimal:4',
        'exchange_rate'     => 'decimal:6',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function source()
    {
        return $this->belongsTo(GoldMarketSource::class, 'source_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForMarket($query, string $marketKey)
    {
        return $query->where('market_key', $marketKey);
    }

    public function scopeValid($query)
    {
        return $query->where('validation_passed', true)->where('anomaly_detected', false);
    }

    public function scopeForInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }
}
