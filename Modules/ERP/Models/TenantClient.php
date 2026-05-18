<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantClient extends TenantModel
{
    protected $fillable = [
        'tenant_id', 'name', 'email', 'phone', 'address', 'currency', 'country_code', 'referral_code', 'referred_by', 'status'
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'referred_by');
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

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'client_id');
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
}

