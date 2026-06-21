<?php

namespace Modules\GoldSavers\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldWallet extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'goal_type',
        'target_grams',
        'target_amount',
        'balance_grams',
        'balance_amount',
        'currency_id',
        'is_active',
    ];

    protected $casts = [
        'target_grams' => 'float',
        'target_amount' => 'float',
        'balance_grams' => 'float',
        'balance_amount' => 'float',
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(GoldTransaction::class, 'wallet_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }

    public function recalculateBalance()
    {
        $this->balance_grams = 0;
        $this->balance_amount = 0;

        foreach ($this->transactions()->get() as $tx) {
            if (in_array($tx->type, ['buy', 'transfer_in'])) {
                $this->balance_grams += $tx->grams;
                $this->balance_amount += $tx->total_amount;
            } elseif (in_array($tx->type, ['sell', 'transfer_out'])) {
                $this->balance_grams -= $tx->grams;
                $this->balance_amount -= $tx->total_amount;
            }
        }

        $this->save();
    }

    protected static function newFactory()
    {
        return \Database\Factories\GoldWalletFactory::new();
    }
}
