<?php

namespace Modules\GoldSavers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'goal_type',
        'target_grams',
        'target_amount',
        'balance_grams',
        'balance_amount',
        'currency',
        'is_active',
    ];

    public function transactions()
    {
        return $this->hasMany(GoldTransaction::class, 'wallet_id');
    }

    public function recalculateBalance()
    {
        $this->balance_grams = 0;
        $this->balance_amount = 0;

        foreach ($this->transactions as $tx) {
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
}
