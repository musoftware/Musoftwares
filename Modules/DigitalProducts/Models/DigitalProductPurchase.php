<?php

namespace Modules\DigitalProducts\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigitalProductPurchase extends Model
{
    use HasFactory;

    protected $table = 'digital_product_purchases';

    protected $fillable = [
        'user_id',
        'digital_product_id',
        'amount_paid',
        'currency_id',
        'payment_method',
        'transaction_id',
        'status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(DigitalProduct::class, 'digital_product_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }
}
