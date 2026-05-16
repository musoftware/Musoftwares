<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    protected ExchangeRateService $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function credit(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null)
    {
        return DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            // Convert amount to wallet's currency
            [$originalAmount, $originalCurrency, $convertedAmount, $convertedCurrency, $exchangeRate, $exchangeDate] =
                $this->exchangeRateService->convertAmount($amount, $currency, $wallet->currency);

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $convertedAmount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $convertedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => $note ?? "Credit to wallet",
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            return $transaction;
        });
    }

    public function debit(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null)
    {
        return DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            // Convert amount to wallet's currency
            [$originalAmount, $originalCurrency, $convertedAmount, $convertedCurrency, $exchangeRate, $exchangeDate] =
                $this->exchangeRateService->convertAmount($amount, $currency, $wallet->currency);

            $availableBalance = $wallet->balance - ($wallet->locked_balance ?? 0);

            if ($availableBalance < $convertedAmount) {
                throw new Exception("Insufficient funds in wallet.");
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $convertedAmount;

            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit',
                'amount' => $convertedAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => $note ?? "Debit from wallet",
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            return $transaction;
        });
    }

    public function getBalance(Wallet $wallet): float
    {
        return (float) $wallet->balance;
    }

    public function lockAmount(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            $availableBalance = $wallet->balance - ($wallet->locked_balance ?? 0);

            if ($availableBalance < $amount) {
                throw new Exception("Insufficient available funds to lock.");
            }

            $wallet->locked_balance = ($wallet->locked_balance ?? 0) + $amount;
            $wallet->save();

            // create a record of locking the amount
            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'debit', // technically it reduces available, we use debit or 'lock' if supported? The schema allows 'credit', 'debit'
                'amount' => 0, // Since it doesn't change actual balance, but we must record. Wait, if it doesn't change `balance`, what does `balance_before` and `after` hold?
                // Actually the prompt says "All methods use ExchangeRateService internally. All create immutable wallet_transactions"
                // But `lockAmount` doesn't change the actual `balance`, only `locked_balance`.
                // Let's create a transaction with amount 0 but add it in description?
                // Or maybe we treat lock as a debit of available balance? No, balance_before/after are for `balance`.
                // We'll record amount=0.
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
                'reference_type' => 'lock',
                'description' => "Locked amount: {$amount}",
            ]);
        });
    }

    public function unlockAmount(Wallet $wallet, float $amount): void
    {
        DB::transaction(function () use ($wallet, $amount) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            if (($wallet->locked_balance ?? 0) < $amount) {
                throw new Exception("Amount to unlock exceeds currently locked balance.");
            }

            $wallet->locked_balance -= $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit', // 'credit' to available? Again, amount 0.
                'amount' => 0,
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance,
                'reference_type' => 'unlock',
                'description' => "Unlocked amount: {$amount}",
            ]);
        });
    }
}
