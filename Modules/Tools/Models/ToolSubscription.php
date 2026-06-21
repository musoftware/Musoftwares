<?php

namespace Modules\Tools\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ToolSubscription extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'tool_guid', 'plan_guid', 'billing_cycle',
        'amount_paid', 'currency_id', 'status', 'payment_method',
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

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }


    public function getToolAttribute()
    {
        return config("tools.{$this->tool_guid}");
    }

    public function getPlanAttribute()
    {
        $tool = $this->tool;
        if ($tool && isset($tool['plans'][$this->plan_guid])) {
            return $tool['plans'][$this->plan_guid];
        }
        return null;
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
            'tool_guid'             => $this->tool_guid,
            'tool_subscription_id'  => $this->id,
            'status'                => 'active',
            'expires_at'            => $this->expires_at,
        ]);
    }
}
