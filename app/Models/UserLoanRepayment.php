<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLoanRepayment extends Model
{
    use HasFactory, ScopesToLegalWindow, SoftDeletes;

    protected $fillable = [
        'user_loan_id',
        'amount',
        'date',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(UserLoan::class, 'user_loan_id');
    }
}
