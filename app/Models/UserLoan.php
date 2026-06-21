<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLoan extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'paid_amount',
        'currency_id',
        'status',
        'date',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function repayments()
    {
        return $this->hasMany(UserLoanRepayment::class);
    }
}
