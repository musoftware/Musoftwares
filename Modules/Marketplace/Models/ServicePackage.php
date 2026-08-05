<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $service_id
 * @property string $name
 * @property string|null $description
 * @property float $price
 * @property float|null $old_price
 * @property int|null $currency_id
 * @property int|null $delivery_days
 * @property int|null $revisions
 * @property array|null $features
 * @property-read bool $has_discount
 * @property-read int $discount_percentage
 */
class ServicePackage extends Model
{
    use SoftDeletes;
    protected $table = 'marketplace_packages';

    protected $fillable = [
        'service_id',
        'name',
        'description',
        'price',
        'old_price',
        'currency_id',
        'delivery_days',
        'revisions',
        'features',
    ];

    protected $casts = [
        'price' => 'float',
        'old_price' => 'float',
        'features' => 'array',
    ];

    protected $appends = ['discount_percentage', 'has_discount'];

    public function getHasDiscountAttribute(): bool
    {
        return !is_null($this->old_price) && (float)$this->old_price > (float)$this->price && (float)$this->price >= 0;
    }

    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->has_discount || (float)$this->old_price <= 0) {
            return 0;
        }

        return (int) round((((float)$this->old_price - (float)$this->price) / (float)$this->old_price) * 100);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}
