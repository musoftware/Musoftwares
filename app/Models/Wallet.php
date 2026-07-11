<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function owner()
    {
        return $this->morphTo();
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }
}
