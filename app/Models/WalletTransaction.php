<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
