<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ToolPricingPlan extends Model
{
    protected $fillable = [
        'tool_id', 'name', 'price_monthly', 'price_yearly',
        'max_devices', 'features', 'is_popular', 'sort_order',
    ];

    protected $casts = [
        'price_monthly' => 'float',
        'price_yearly'  => 'float',
        'features'      => 'array',
        'is_popular'    => 'boolean',
        'max_devices'   => 'integer',
        'sort_order'    => 'integer',
    ];

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ToolSubscription::class);
    }

    public function getYearlySavingsAttribute(): float
    {
        return round((($this->price_monthly * 12) - $this->price_yearly) / ($this->price_monthly * 12) * 100);
    }
}
