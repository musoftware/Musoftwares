<?php

namespace App\Services;

use App\Exceptions\WithdrawalRejectedException;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserReferralRequestWithdraw;

/**
 * Balance management service providing reconciliation and computation utilities.
 * Recovered from old project: App\Helpers\BalancesHelper
 * Modernized: Service-oriented, legacy-based (user-field-based).
 */
class BalanceService extends BaseService
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
            ->whereIn('type', ['paid', 'used', 'withdrawn', 'sent'])
            ->sum('amount');

        $calculatedBalance = round($credits - $debits, 2);

        if (abs($calculatedBalance - (float) $user->user_balance) > 0.01) {
            $user->user_balance = $calculatedBalance;
            $user->save();
        }

        return $calculatedBalance;
    }

    /**
     * Calculate the pending withdrawal amount (held in reserve).
     * Recovered from old project: BalancesHelper::CalcWithdrawingCommission()
     */
    public function pendingWithdrawalAmount(User $user): float
    {
        return (float) UserReferralRequestWithdraw::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');
    }

    /**
     * Calculate total withdrawn (completed withdrawals).
     * Recovered from old project: BalancesHelper::CalcWithdrawnCommission()
     */
    public function totalWithdrawn(User $user): float
    {
        return (float) UserReferralRequestWithdraw::where('user_id', $user->id)
            ->where('status', 'approved')
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

        if (! $user->kyc_verified) {
            return ['eligible' => false, 'reason' => 'Identity verification (KYC) is required before requesting a withdrawal.'];
        }

        if ($amount < $minimumWithdrawal) {
            return ['eligible' => false, 'reason' => "Minimum withdrawal amount is {$minimumWithdrawal}."];
        }

        $availableEarned = $this->availableEarnedBalance($user);
        if ($amount > $availableEarned) {
            return ['eligible' => false, 'reason' => "Insufficient earned balance. Available: {$availableEarned}."];
        }

        $walletBalance = round((float) $user->user_balance, 2);
        if ($amount > $walletBalance) {
            return ['eligible' => false, 'reason' => "Insufficient wallet balance. Available: {$walletBalance}."];
        }

        $payoutMethod = $user->payoutMethods()->where('id', $payoutMethodId)->first();
        if (! $payoutMethod) {
            return ['eligible' => false, 'reason' => 'Invalid payout method.'];
        }

        if ($payoutMethod->status !== 'approved') {
            return ['eligible' => false, 'reason' => 'Payout method is not approved.'];
        }

        return ['eligible' => true];
    }

    /**
     * Process a withdrawal request.
     *
     * Locks the user row, re-checks eligibility on fresh data and only then
     * debits the wallet, so parallel requests cannot withdraw the same funds.
     *
     * @throws WithdrawalRejectedException when the locked re-check fails
     */
    public function processWithdrawalRequest(User $user, float $amount, int $payoutMethodId): UserReferralRequestWithdraw
    {
        return $this->executeInTransaction(function () use ($user, $amount, $payoutMethodId) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $eligibility = $this->validateWithdrawalEligibility($lockedUser, $amount, $payoutMethodId);
            if (! $eligibility['eligible']) {
                throw new WithdrawalRejectedException($eligibility['reason']);
            }

            $payoutMethod = $lockedUser->payoutMethods()->whereKey($payoutMethodId)->firstOrFail();
            $lockedUser->add_balance(-1 * $amount, 'Withdrawal request via '.ucwords(str_replace('_', ' ', $payoutMethod->type)), 'used');

            $withdrawal = new UserReferralRequestWithdraw;
            $withdrawal->user_id = $lockedUser->id;
            $withdrawal->amount = $amount;
            $withdrawal->currency = $lockedUser->currency;
            $withdrawal->payment_method = $payoutMethod->type;
            $withdrawal->payment_info = json_encode($payoutMethod->details ?? []);
            $withdrawal->user_payment_method_id = $payoutMethod->id;
            $withdrawal->status = 'pending';
            $withdrawal->save();

            return $withdrawal;
        });
    }

    /**
     * Credit a Kashier wallet deposit exactly once. Single entry point for every Kashier deposit handler.
     * The payment_provider_events row is inserted in the same transaction as the credit, so a
     * duplicate or concurrent delivery hits the UNIQUE(provider, external_id) key and credits nothing.
     * The credited amount is the signed charge, converted from its currency by add_balance().
     */
    public function processKashierDepositWebhook(User $user, \App\Helpers\KashierSignedAmount $paid, string $trxId, array $payload = []): array
    {
        $reason = "Deposit via Kashier online payment (Trx: $trxId)";

        $credited = $this->executeInTransaction(function () use ($user, $paid, $trxId, $payload, $reason) {
            if (! \App\Models\PaymentProviderEvent::recordOnce(\App\Models\PaymentProviderEvent::PROVIDER_KASHIER, $trxId, $payload)) {
                return false;
            }

            $user->add_balance($paid->amount, $reason, 'received', $paid->currencyId);

            return true;
        }, "Kashier deposit credit failed for user {$user->id}, trx {$trxId}");

        if (! $credited) {
            \Illuminate\Support\Facades\Log::info('Duplicate Kashier deposit webhook skipped.', ['user_id' => $user->id, 'trx' => $trxId]);

            return ['status' => 'success', 'message' => 'Already processed', 'already_processed' => true];
        }

        \Illuminate\Support\Facades\Log::info('Kashier deposit credited.', [
            'user_id' => $user->id,
            'trx' => $trxId,
            'amount' => $paid->amount,
            'currency' => $paid->currencyCode,
        ]);

        return ['status' => 'success', 'message' => 'Deposit processed successfully', 'already_processed' => false];
    }
}
