<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformPlan extends Model
{
    protected $fillable = [
        'slug', 'name', 'description',
        'monthly_price', 'yearly_price',
        'included_modules', 'included_tools', 'features',
        'is_custom', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'monthly_price'    => 'decimal:2',
        'yearly_price'     => 'decimal:2',
        'included_modules' => 'array',
        'included_tools'   => 'array',
        'features'         => 'array',
        'is_custom'        => 'boolean',
        'is_active'        => 'boolean',
    ];

    /**
     * All subscriptions on this plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(PlatformSubscription::class, 'plan_id');
    }

    /**
     * Check if this plan grants access to a specific module.
     */
    public function includesModule(string $module): bool
    {
        $modules = $this->included_modules ?? [];
        return in_array('*', $modules) || in_array($module, $modules);
    }

    /**
     * Check if this plan grants access to a specific tool.
     */
    public function includesTool(string $toolSlug): bool
    {
        $tools = $this->included_tools ?? [];
        return in_array('*', $tools) || in_array($toolSlug, $tools);
    }

    /**
     * Get the price for a given billing cycle.
     */
    public function priceFor(string $cycle): float
    {
        return (float) ($cycle === 'yearly' ? $this->yearly_price : $this->monthly_price);
    }

    /**
     * Scope: only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
