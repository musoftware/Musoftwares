<?php

namespace Modules\DigitalProducts\Models;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $digital_product_id
 * @property float|string $amount_paid
 * @property int|null $currency_id
 * @property string $payment_method
 * @property string|null $transaction_id
 * @property string $status
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read \Modules\DigitalProducts\Models\DigitalProduct|null $product
 * @property-read \App\Models\Currency|null $currency
 */
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
