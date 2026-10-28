<?php

namespace App\Models;

use App\Services\AI\ScopePricingEngine;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * The one price list. Contracts, the AI pricing and the public estimator all read it; an item with
 * a platform (web, landing, mobile, desktop) is also shown in the estimator.
 */
class ContractPriceItem extends Model
{
    use HasFactory;

    public const PLATFORMS = ['web', 'landing', 'mobile', 'desktop'];

    protected $table = 'contract_price_items';

    protected $fillable = [
        'name',
        'description',
        'default_price',
        'price_usd',
        'currency_id',
        'key',
        'platform',
        'category',
        'icon',
        'meta',
        'name_ar',
        'name_en',
        'standalone_hours',
        'marginal_hours',
        'complexity',
        'keywords',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'default_price'    => 'decimal:2',
        'price_usd'        => 'decimal:2',
        'standalone_hours' => 'integer',
        'marginal_hours'   => 'integer',
        'keywords'         => 'array',
        'meta'             => 'array',
        'is_active'        => 'boolean',
        'sort_order'       => 'integer',
    ];

    public static function hourlyRateUsd(): float
    {
        $rate = (float) AdminSettings::GetValue('market_hourly_rate', ScopePricingEngine::BASE_HOURLY_RATE_USD);

        return $rate > 0 ? $rate : ScopePricingEngine::BASE_HOURLY_RATE_USD;
    }

    /** The fixed price when the item has one, otherwise its hours at the hourly rate. */
    public function unitPriceUsd(?float $hourlyRate = null): float
    {
        if ($this->price_usd !== null && (float) $this->price_usd > 0) {
            return (float) $this->price_usd;
        }

        return round((int) $this->standalone_hours * ($hourlyRate ?? self::hourlyRateUsd()), 2);
    }
}
