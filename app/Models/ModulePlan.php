<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModulePlan extends Model
{
    use SoftDeletes;

    protected $table = 'module_plans';

    protected $fillable = [
        'module',
        'name',
        'price',
        'billing',
        'features',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Subscriptions linked to this module.
     * Matches on module_plans.module == user_subscriptions.object
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class, 'object', 'module');
    }
}
