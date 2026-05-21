<?php

namespace App\Services;

use App\Models\User;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Modules\Core\Models\UserWithdrawal;
use Modules\Core\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;

/**
 * Balance management service providing reconciliation and computation utilities.
 * Recovered from old project: App\Helpers\BalancesHelper
 * Modernized: Service-oriented, wallet-based (not user-field-based).
 */
class BalanceService
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    /**
     * Recalculate a user's wallet balance from transaction history.
     * This is a safety/reconciliation method to ensure the wallet balance
     * matches the sum of all transactions.
     *
     * Recovered from old project: BalancesHelper::CalcBalance()
     */
    public function recalculateWalletBalance(Wallet $wallet): float
    {
        $wallet = Wallet::lockForUpdate()->find($wallet->id);

        $credits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->sum('amount');

        $debits = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->sum('amount');

        $calculatedBalance = round($credits - $debits, 2);

        if (abs($calculatedBalance - (float) $wallet->balance) > 0.01) {
            $wallet->update(['balance' => $calculatedBalance]);
        }

        return $calculatedBalance;
    }

    /**
     * Calculate the pending withdrawal amount (held in reserve).
     * Recovered from old project: BalancesHelper::CalcWithdrawingCommission()
     */
    public function pendingWithdrawalAmount(User $user): float
    {
        return (float) UserWithdrawal::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->sum('amount');
    }

    /**
     * Calculate total withdrawn (completed withdrawals).
     * Recovered from old project: BalancesHelper::CalcWithdrawnCommission()
     */
    public function totalWithdrawn(User $user): float
    {
        return (float) UserWithdrawal::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Get the available balance (total balance minus pending withdrawals).
     */
    public function availableBalance(User $user): float
    {
        $wallet = $user->getWallet();
        $pending = $this->pendingWithdrawalAmount($user);
        return max(0, round((float) $wallet->balance - $pending, 2));
    }

    /**
     * Get the available earned balance (for withdrawals — only earned funds).
     */
    public function availableEarnedBalance(User $user): float
    {
        $wallet = $user->getWallet();
        $pending = $this->pendingWithdrawalAmount($user);
        return max(0, round((float) ($wallet->earned_balance ?? 0) - $pending, 2));
    }

    /**
     * Calculate total deposited this month.
     */
    public function totalDepositedThisMonth(User $user): float
    {
        $wallet = $user->getWallet();
        return (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'credit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Calculate total spent (debits) this month.
     */
    public function totalSpentThisMonth(User $user): float
    {
        $wallet = $user->getWallet();
        return (float) WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'debit')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Validate withdrawal eligibility.
     * Checks: minimum amount, available earned balance, approved payout method.
     *
     * @return array{eligible: bool, reason?: string}
     */
    public function validateWithdrawalEligibility(User $user, float $amount, int $payoutMethodId): array
    {
        $minimumWithdrawal = (float) config('app.minimum_withdrawal', 50);

        if (!$user->kyc_verified) {
            return ['eligible' => false, 'reason' => "Identity verification (KYC) is required before requesting a withdrawal."];
        }

        if ($amount < $minimumWithdrawal) {
            return ['eligible' => false, 'reason' => "Minimum withdrawal amount is {$minimumWithdrawal}."];
        }

        $availableEarned = $this->availableEarnedBalance($user);
        if ($amount > $availableEarned) {
            return ['eligible' => false, 'reason' => "Insufficient earned balance. Available: {$availableEarned}."];
        }

        $payoutMethod = $user->payoutMethods()->where('id', $payoutMethodId)->first();
        if (!$payoutMethod) {
            return ['eligible' => false, 'reason' => 'Invalid payout method.'];
        }

        if ($payoutMethod->status !== 'approved') {
            return ['eligible' => false, 'reason' => 'Payout method is not approved.'];
        }

        return ['eligible' => true];
    }
}
