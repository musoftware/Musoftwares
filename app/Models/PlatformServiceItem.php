<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformServiceItem extends Model
{
    protected $fillable = [
        'type', 'slug', 'name', 'description',
        'monthly_price', 'yearly_price',
        'icon', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price'  => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    /**
     * Get the price for a given billing cycle.
     */
    public function priceFor(string $cycle): float
    {
        return (float) ($cycle === 'yearly' ? $this->yearly_price : $this->monthly_price);
    }

    /**
     * Scope: only active items.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: modules only.
     */
    public function scopeModules($query)
    {
        return $query->where('type', 'module');
    }

    /**
     * Scope: tools only.
     */
    public function scopeTools($query)
    {
        return $query->where('type', 'tool');
    }
}
