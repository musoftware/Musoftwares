<?php

namespace Modules\GoldSavers\Services;

use Modules\GoldSavers\Models\GoldWallet;
use Modules\GoldSavers\Models\GoldTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class GoldWalletService
{
    /**
     * Create a new gold wallet.
     */
    public function createWallet($userId, $name, $goalType, $targetGrams = 0, $targetAmount = 0, $tenantId = null)
    {
        return GoldWallet::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'name' => $name,
            'goal_type' => $goalType,
            'target_grams' => $targetGrams,
            'target_amount' => $targetAmount,
            'currency_id' => \App\Models\AdminSettings::business_currency()->id ?? null,
        ]);
    }

    /**
     * Add a transaction (buy/sell) to a wallet.
     */
    public function addTransaction(GoldWallet $wallet, array $data)
    {
        DB::beginTransaction();

        try {
            $transaction = $wallet->transactions()->create([
                'type' => $data['type'], // 'buy', 'sell'
                'grams' => $data['grams'],
                'karat' => $data['karat'] ?? 21,
                'price_per_gram' => $data['price_per_gram'],
                'total_amount' => $data['total_amount'],
                'fees' => $data['fees'] ?? 0,
                'currency_id' => $data['currency_id'] ?? $wallet->currency_id ?? \App\Models\AdminSettings::business_currency()->id,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'vendor_name' => $data['vendor_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $wallet->recalculateBalance();

            DB::commit();

            return $transaction;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
