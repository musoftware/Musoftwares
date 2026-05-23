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
        $prices = [
            'starter' => [
                '3_months' => 20,
                '6_months' => 39,
                '1_year'   => 75,
                '3_years'  => 199,
            ],
            'professional' => [
                '3_months' => 42,
                '6_months' => 80,
                '1_year'   => 150,
                '3_years'  => 399,
            ],
            'business_suite' => [
                '3_months' => 85,
                '6_months' => 160,
                '1_year'   => 299,
                '3_years'  => 799,
            ],
        ];

        if (isset($prices[$this->slug][$cycle])) {
            return (float) $prices[$this->slug][$cycle];
        }

        // Fallback for custom or unknown plans
        $months = match ($cycle) {
            '3_months' => 3,
            '6_months' => 6,
            '1_year'   => 12,
            '3_years'  => 36,
            default    => 1, // fallback to 1 month just in case
        };

        return (float) ($this->monthly_price * $months);
    }

    /**
     * Scope: only active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
