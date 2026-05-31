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

    protected $appends = [
        'balance', 'locked_balance', 'avatar_url'
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

    public function getAvatarUrlAttribute()
    {
        if (empty($this->email)) {
            return 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        }
        
        $hash = md5(strtolower(trim($this->email)));
        return "https://www.gravatar.com/avatar/{$hash}?d=404";
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

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'client_id');
    }

    public function balance(): float
    {
        // All transactions reflect correctly on the wallet balance.
        // 'received' and 'earned' are positive.
        // 'refunded', 'sent', and 'used' are negative.
        // Reversals (e.g. cancelled invoices) can result in a positive 'used'.
        return round((float) $this->transactions()->sum('amount'), 2);
    }

    public function getBalanceAttribute(): float
    {
        return $this->balance();
    }

    public function lockedBalance(): float
    {
        $unpaidInvoices = $this->invoices()->whereIn('status', ['sent', 'partial'])->get();
        return round((float) $unpaidInvoices->sum(function ($invoice) {
            return $invoice->unpaidAmount();
        }), 2);
    }

    public function getLockedBalanceAttribute(): float
    {
        return $this->lockedBalance();
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

    public function debtTransactions(): HasMany
    {
        return $this->hasMany(DebtTransaction::class, 'client_id');
    }

    public function debtBalance(): float
    {
        $given = $this->debtTransactions()->where('type', 'given')->sum('amount');
        $received = $this->debtTransactions()->where('type', 'received')->sum('amount');
        return (float) ($given - $received);
    }

    public function getDebtBalanceAttribute(): float
    {
        return $this->debtBalance();
    }
}

