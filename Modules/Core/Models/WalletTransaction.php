<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * WalletTransaction — platform-level user wallet ledger entry.
 *
 * ⚠️  DO NOT confuse with Modules\ERP\Models\ClientWalletTransaction, which tracks
 *     ERP client wallet movements (table: client_wallet_transactions).
 *     This model uses the platform table: wallet_transactions.
 */
class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'type', 'amount', 'balance_before', 'balance_after',
        'reference_type', 'reference_id', 'description', 'business_amount', 'business_currency'
    ];

    // Immutable
    public $timestamps = true;

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
