<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherRedemption extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'voucher_id',
        'user_id',
        'transaction_id',
        'spent_amount',
        'spent_currency',
        'reward_amount',
        'reward_currency',
        'reward_transaction_id',
    ];

    protected $casts = [
        'spent_amount' => 'decimal:10',
        'reward_amount' => 'decimal:10',
    ];

    // Relationships
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function rewardTransaction()
    {
        return $this->belongsTo(Transaction::class, 'reward_transaction_id');
    }

    public function spentCurrency()
    {
        return $this->belongsTo(Currency::class, 'spent_currency');
    }

    public function rewardCurrency()
    {
        return $this->belongsTo(Currency::class, 'reward_currency');
    }
}

