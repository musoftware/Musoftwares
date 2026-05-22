<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WalletTransaction extends Model
{
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'currency',
        'balance_before',
        'balance_after',
        'description',
        'created_by'
    ];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
