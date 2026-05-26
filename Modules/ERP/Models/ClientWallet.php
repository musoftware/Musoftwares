<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientWallet extends TenantModel
{
    protected $table = 'erp_client_wallets';

    protected $fillable = ['tenant_id', 'client_id', 'balance', 'currency', 'locked_balance'];

    protected $casts = [
        'balance' => 'decimal:2',
        'locked_balance' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function platformClient(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'client_id');
    }

    public function tenantClient(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function getClientAttribute()
    {
        return (empty($this->tenant_id) || $this->tenant_id === Tenant::platformId())
            ? $this->platformClient 
            : $this->tenantClient;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }
}
