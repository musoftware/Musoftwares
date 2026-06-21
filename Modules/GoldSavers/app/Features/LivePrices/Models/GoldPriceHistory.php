<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoldPriceHistory extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $table = 'gold_price_history';

    protected $fillable = [
        'tenant_id',
        'source_id',
        'market_key',
        'interval',
        'karat',
        'open_price',
        'high_price',
        'low_price',
        'close_price',
        'avg_price',
        'tick_count',
        'currency_id',
        'period_start',
        'period_end',
    ];

    protected $casts = [
        'open_price'   => 'decimal:4',
        'high_price'   => 'decimal:4',
        'low_price'    => 'decimal:4',
        'close_price'  => 'decimal:4',
        'avg_price'    => 'decimal:4',
        'period_start' => 'datetime',
        'period_end'   => 'datetime',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────────

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
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

    public function scopeForInterval($query, string $interval)
    {
        return $query->where('interval', $interval);
    }

    public function scopeForKarat($query, int $karat)
    {
        return $query->where('karat', $karat);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('period_start', [$from, $to]);
    }
}
