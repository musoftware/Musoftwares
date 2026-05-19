<?php

namespace Modules\Tools\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ToolSubscription extends Model
{
    protected $fillable = [
        'user_id', 'tool_id', 'tool_pricing_plan_id', 'billing_cycle',
        'amount_paid', 'currency', 'status', 'payment_method',
        'payment_reference', 'starts_at', 'expires_at', 'cancelled_at',
    ];

    protected $casts = [
        'starts_at'    => 'datetime',
        'expires_at'   => 'datetime',
        'cancelled_at' => 'datetime',
        'amount_paid'  => 'float',
    ];

    public static array $statuses = ['active', 'cancelled', 'expired', 'suspended'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function tool(): BelongsTo
    {
        return $this->belongsTo(Tool::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(ToolPricingPlan::class, 'tool_pricing_plan_id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(ToolLicense::class, 'tool_subscription_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Issue a fresh license key when a subscription is created.
     */
    public function issueLicense(): ToolLicense
    {
        return ToolLicense::create([
            'license_key'           => Str::uuid(),
            'user_id'               => $this->user_id,
            'tool_id'               => $this->tool_id,
            'tool_subscription_id'  => $this->id,
            'status'                => 'active',
            'expires_at'            => $this->expires_at,
        ]);
    }
}
