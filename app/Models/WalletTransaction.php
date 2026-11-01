<?php

namespace App\Models;

use App\Models\Concerns\ScopesToLegalWindow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WalletTransaction extends Model
{
    use ScopesToLegalWindow, SoftDeletes;

    protected $guarded = [];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
