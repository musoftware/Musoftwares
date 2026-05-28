<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WalletTransaction — ERP ledger entry on a TenantClient's wallet.
 *
 * ⚠️  FINANCIAL ISOLATION: Do NOT confuse with App\Models\WalletTransaction
 *     which tracks REAL platform money (table: wallet_transactions).
 *
 *     This model is ERP-internal ONLY (table: client_wallet_transactions).
 *     It records credit/debit movements on a tenant's client bookkeeping ledger.
 */
class WalletTransaction extends TenantModel
{
    protected $table = 'erp_client_wallet_transactions';

    protected $fillable = [
        'tenant_id', 'client_id', 'project_id', 'type', 'direction',
        'amount', 'currency_id', 'business_amount', 'business_currency_id',
        'exchange_rate', 'exchange_rate_date',
        'reference_type', 'reference_id', 'note', 'created_by'
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(TenantClient::class, 'client_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function businessCurrency(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Currency::class, 'business_currency_id');
    }
}
