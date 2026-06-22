<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected static function booted()
    {
        static::saving(function ($item) {
            $item->total = $item->qty * $item->amount;
        });
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
