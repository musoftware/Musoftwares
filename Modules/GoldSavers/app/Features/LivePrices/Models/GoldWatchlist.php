<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoldWatchlist extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'market_keys',
        'tracked_karats',
        'tracked_currencies',
        'is_default',
    ];

    protected $casts = [
        'market_keys'        => 'array',
        'tracked_karats'     => 'array',
        'tracked_currencies' => 'array',
        'is_default'         => 'boolean',
    ];

    // ─── Scopes ─────────────────────────────────────────────────────────────────

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Business Logic ─────────────────────────────────────────────────────────

    public function tracksMarket(string $marketKey): bool
    {
        return in_array($marketKey, $this->market_keys ?? []);
    }

    public function tracksKarat(int $karat): bool
    {
        return in_array($karat, $this->tracked_karats ?? []);
    }
}
