<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VoucherService
{
    /**
     * Check and apply vouchers when user makes a transaction
     */
    public function checkAndApplyVouchers(User $user, Transaction $transaction): void
    {
        // Only check for 'used' or 'sent' transactions (spending transactions)
        if (!in_array($transaction->type, ['used', 'sent'])) {
            return;
        }

        // Get all active vouchers
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->get();

        foreach ($vouchers as $voucher) {
            // Check if voucher can be used by this user
            if (!$voucher->canBeUsedByUser($user)) {
                continue;
            }

            // Convert transaction amount to voucher's spend currency
            // Note: Adhering to ERP Financial Rules, we must use RateByDate if there is a mismatch.
            $transactionAmountInVoucherCurrency = $transaction->currency_id == $voucher->spend_currency_id
                ? abs($transaction->amount)
                : CurrenciesExchange::RateByDate($transaction->created_at ?? now(), abs($transaction->amount), $transaction->currency_id, $voucher->spend_currency_id);

            // Check if transaction amount meets the spend requirement
            if ($transactionAmountInVoucherCurrency >= $voucher->spend_amount) {
                $this->applyVoucher($user, $transaction, $voucher, $transactionAmountInVoucherCurrency);
            }
        }
    }

    /**
     * Apply a voucher reward to a user
     */
    protected function applyVoucher(User $user, Transaction $transaction, Voucher $voucher, float $spentAmount): void
    {
        try {
            DB::beginTransaction();

            // Calculate reward
            $rewardAmount = $voucher->calculateReward($spentAmount);

            // Convert reward to user's currency if needed
            $userRewardAmount = $voucher->reward_currency_id == $user->currency_id
                ? $rewardAmount
                : CurrenciesExchange::RateByDate(now(), $rewardAmount, $voucher->reward_currency_id, $user->currency_id);

            // Add reward to user balance
            $rewardTransactionId = $user->add_balance(
                $userRewardAmount,
                'Voucher Reward: ' . $voucher->name,
                'earned'
            );

            // Create redemption record
            VoucherRedemption::create([
                'voucher_id' => $voucher->id,
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'spent_amount' => $spentAmount,
                'spent_currency' => $voucher->spend_currency_id,
                'reward_amount' => $userRewardAmount,
                'reward_currency' => $user->currency_id,
                'reward_transaction_id' => $rewardTransactionId,
            ]);

            // Update voucher usage count
            $voucher->increment('current_uses');

            DB::commit();

            Log::info('Voucher applied', [
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
                'reward_amount' => $userRewardAmount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to apply voucher', [
                'user_id' => $user->id,
                'voucher_id' => $voucher->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
