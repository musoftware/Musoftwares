<?php

namespace App\Models;

use App\Services\ClientSystems\LicenseKeys;
use App\Services\ClientSystems\SystemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * A client's own system that we license (monthly price and/or a share of its sales).
 * The API secret signs its requests to us; the signing key signs the licenses we send back.
 */
class ClientSystem extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'site_url', 'currency_id', 'monthly_price', 'sales_percent', 'grace_days',
        'next_bill_at', 'open_until', 'manually_locked',
    ];

    protected $hidden = ['api_secret', 'signing_secret_key'];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'sales_percent' => 'decimal:2',
        'grace_days' => 'integer',
        'api_secret' => 'encrypted',
        'signing_secret_key' => 'encrypted',
        'next_bill_at' => 'datetime',
        'open_until' => 'datetime',
        'manually_locked' => 'boolean',
        'last_check_in_at' => 'datetime',
        'fingerprint_changed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $system) {
            $system->uuid ??= (string) Str::uuid();
            $system->fillNewKeys();
        });
    }

    /** New API secret and signing key pair. Old integrations stop working until the new prompt is applied. */
    public function fillNewKeys(): void
    {
        $pair = LicenseKeys::create();
        $this->api_key = 'cs_'.Str::lower(Str::random(32));
        $this->api_secret = Str::random(48);
        $this->signing_public_key = $pair['public'];
        $this->signing_secret_key = $pair['private'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function bills(): HasMany
    {
        return $this->hasMany(ClientSystemBill::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ClientSystemSale::class);
    }

    public function chargesSales(): bool
    {
        return (float) $this->sales_percent > 0;
    }

    /** What the admin lists show for a system. */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'site_url' => $this->site_url,
            'user' => $this->user?->only(['id', 'name', 'email']),
            'currency' => $this->currency?->currency,
            'monthly_price' => (float) $this->monthly_price,
            'sales_percent' => (float) $this->sales_percent,
            'state' => SystemStatus::of($this, now())->state,
            'last_check_in_at' => $this->last_check_in_at?->toIso8601String(),
            'has_alert' => $this->pending_fingerprint !== null,
        ];
    }
}
