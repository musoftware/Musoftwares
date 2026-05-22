<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PlatformSubscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'billing_cycle',
        'amount', 'currency', 'status',
        'started_at', 'expires_at', 'auto_renew',
        'custom_items', 'payment_method', 'payment_reference',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'auto_renew'   => 'boolean',
        'custom_items' => 'array',
        'started_at'   => 'datetime',
        'expires_at'   => 'datetime',
    ];

    public static array $statuses = ['active', 'cancelled', 'expired', 'suspended'];

    /* ─── Relationships ─── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PlatformPlan::class, 'plan_id');
    }

    /* ─── Status Helpers ─── */

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /* ─── Access Checks ─── */

    /**
     * Check if this subscription grants access to a module.
     * Works for both fixed plans and custom plans.
     */
    public function hasAccessToModule(string $module): bool
    {
        // Custom plan: check the custom_items array
        if ($this->plan && $this->plan->is_custom) {
            $items = $this->custom_items ?? [];
            return in_array($module, $items);
        }

        // Fixed plan: delegate to the plan
        if ($this->plan) {
            return $this->plan->includesModule($module);
        }

        return false;
    }

    /**
     * Check if this subscription grants access to a tool.
     * Works for both fixed plans and custom plans.
     */
    public function hasAccessToTool(string $toolSlug): bool
    {
        // Custom plan: check the custom_items array
        if ($this->plan && $this->plan->is_custom) {
            $items = $this->custom_items ?? [];
            return in_array($toolSlug, $items);
        }

        // Fixed plan: delegate to the plan
        if ($this->plan) {
            return $this->plan->includesTool($toolSlug);
        }

        return false;
    }

    /* ─── Scopes ─── */

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', Carbon::now());
            });
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
