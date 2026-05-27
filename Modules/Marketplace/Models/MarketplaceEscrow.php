<?php

namespace Modules\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\WalletTransaction;

class MarketplaceEscrow extends Model
{
    protected $fillable = [
        'order_id',
        'buyer_wallet_transaction_id',
        'seller_wallet_transaction_id',
        'amount',
        'currency_id',
        'business_amount',
        'business_currency_id',
        'exchange_rate',
        'exchange_rate_date',
        'status',
        'released_at',
        'refunded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'business_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:8',
        'exchange_rate_date' => 'date',
        'released_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function buyerTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'buyer_wallet_transaction_id');
    }

    public function sellerTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class, 'seller_wallet_transaction_id');
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
