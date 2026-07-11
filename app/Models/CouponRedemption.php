<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CouponRedemption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'coupon_id',
        'user_id',
        'transaction_id',
        'discount_amount',
        'currency',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:10',
    ];

    // Relationships
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function currencyRelation()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }
}
