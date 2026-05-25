<?php

namespace Modules\GoldSavers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'grams',
        'karat',
        'price_per_gram',
        'total_amount',
        'fees',
        'currency_id',
        'transaction_date',
        'vendor_name',
        'invoice_path',
        'notes',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function wallet()
    {
        return $this->belongsTo(GoldWallet::class, 'wallet_id');
    }
}
