<?php

namespace Modules\ERP\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WalletTransaction — ERP ledger entry on a TenantClient's wallet.
 *
 * ⚠️  FINANCIAL ISOLATION: Do NOT confuse with Modules\Core\Models\WalletTransaction
 *     which tracks REAL platform money (table: wallet_transactions).
 *
 *     This model is ERP-internal ONLY (table: client_wallet_transactions).
 *     It records credit/debit movements on a tenant's client bookkeeping ledger.
 */
class WalletTransaction extends TenantModel
{
    protected $table = 'client_wallet_transactions';

    protected $fillable = [
        'tenant_id', 'wallet_id', 'type', 'direction',
        'amount', 'amount_currency', 'business_amount', 'business_currency',
        'exchange_rate', 'exchange_rate_date', 'balance_before', 'balance_after',
        'reference_type', 'reference_id', 'note', 'created_by'
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'amount' => 'decimal:2',
        'business_amount' => 'decimal:2',
        'exchange_rate' => 'decimal:6',
        'exchange_rate_date' => 'date',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(ClientWallet::class, 'wallet_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
