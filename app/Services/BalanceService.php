<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Modules\Core\Models\UserWithdrawal;
use Modules\Core\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;

/**
 * Balance management service providing reconciliation and computation utilities.
 * Recovered from old project: App\Helpers\BalancesHelper
 * Modernized: Service-oriented, legacy-based (user-field-based).
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
    public function recalculateUserBalance(User $user): float
    {
        $credits = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['received', 'earned'])
            ->sum('amount');

        $debits = Transaction::where('user_id', $user->id)
            ->whereIn('type', ['paid', 'used', 'withdrawn'])
            ->sum('amount');

        $calculatedBalance = round($credits - $debits, 2);

        if (abs($calculatedBalance - (float) $user->user_balance) > 0.01) {
            $user->update(['user_balance' => $calculatedBalance]);
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
        $pending = $this->pendingWithdrawalAmount($user);
        return max(0, round((float) $user->user_balance - $pending, 2));
    }

    /**
     * Get the available earned balance (for withdrawals — only earned funds).
     */
    public function availableEarnedBalance(User $user): float
    {
        $pending = $this->pendingWithdrawalAmount($user);
        return max(0, round((float) ($user->pending_commission ?? 0) - $pending, 2));
    }

    /**
     * Calculate total deposited this month.
     */
    public function totalDepositedThisMonth(User $user): float
    {
        return (float) Transaction::where('user_id', $user->id)
            ->whereIn('type', ['received', 'earned'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');
    }

    /**
     * Calculate total spent (debits) this month.
     */
    public function totalSpentThisMonth(User $user): float
    {
        return (float) Transaction::where('user_id', $user->id)
            ->whereIn('type', ['paid', 'used', 'withdrawn'])
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
