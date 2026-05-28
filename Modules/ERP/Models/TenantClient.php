<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantClient extends TenantModel
{
    protected $table = 'erp_tenant_clients';

    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'email', 'phone', 'address', 'currency_id',
        'country_id', 'status', 'referral_code', 'referred_by'
    ];

    protected static function booted()
    {
        parent::booted();

        static::creating(function ($client) {
            if (empty($client->referral_code)) {
                $client->referral_code = 'REF-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function wallet()
    {
        return $this->hasOne(ClientWallet::class, 'client_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'client_id')->latest();
    }

    /**
     * Admin notes on this client.
     * Parallel to platform-level UserNote (admin notes on platform users).
     * Recovered from old project: UserCredential model.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class, 'client_id');
    }

    /**
     * Task boards created for this client.
     * Recovered from old project: Task model (user_id → client_id).
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(ERPTask::class, 'client_id');
    }

    /**
     * Support tickets filed for this client.
     * Gated behind the erp-tickets addon.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'client_id');
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(TenantClient::class, 'referred_by');
    }
}

