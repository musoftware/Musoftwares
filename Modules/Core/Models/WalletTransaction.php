<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id', 'type', 'amount', 'balance_before', 'balance_after',
        'reference_type', 'reference_id', 'description'
    ];

    // Immutable
    public $timestamps = true;

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
