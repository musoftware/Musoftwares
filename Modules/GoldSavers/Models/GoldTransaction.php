<?php

namespace Modules\GoldSavers\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class GoldTransaction extends Model
{
    use SoftDeletes, HasFactory;

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

    protected function type(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $val = strtolower(trim((string) $value));
                if ($val === '1') return 'buy';
                if ($val === '2') return 'sell';
                return $val;
            }
        );
    }
}
