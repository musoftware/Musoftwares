<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Events\WalletCredited;
use App\Events\WalletDebited;

class WalletService
{
    protected ExchangeRateService $exchangeRateService;
    protected LedgerService $ledgerService;

    public function __construct(ExchangeRateService $exchangeRateService, LedgerService $ledgerService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->ledgerService = $ledgerService;
    }

    public function creditAvailable(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null)
    {
        return DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

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
                'description' => $note ?? "Credit to wallet available balance",
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            $this->ledgerService->recordTransaction([
                'tenant_id' => null,
                'account_type' => 'wallet',
                'account_id' => $wallet->id,
                'amount' => $convertedAmount,
                'amount_currency' => $wallet->currency,
                'business_amount' => $convertedAmount,
                'business_currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeDate,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => $note ?? "Credit to wallet available balance",
                'created_by' => auth()->id() ?? null,
            ], [
                'tenant_id' => null,
                'account_type' => 'system',
                'account_id' => null,
                'amount' => $convertedAmount,
                'amount_currency' => $wallet->currency,
                'business_amount' => $convertedAmount,
                'business_currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeDate,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => 'System offset for wallet credit',
                'created_by' => auth()->id() ?? null,
            ]);

            event(new WalletCredited($transaction));

            return $transaction;
        });
    }

    public function debitAvailable(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null)
    {
        return DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            [$originalAmount, $originalCurrency, $convertedAmount, $convertedCurrency, $exchangeRate, $exchangeDate] =
                $this->exchangeRateService->convertAmount($amount, $currency, $wallet->currency);

            $availableBalance = $wallet->balance - ($wallet->locked_balance ?? 0);

            if ($availableBalance < $convertedAmount) {
                throw new Exception("Insufficient available funds in wallet.");
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
                'description' => $note ?? "Debit from wallet available balance",
            ]);

            $wallet->update(['balance' => $balanceAfter]);

            $this->ledgerService->recordTransaction([
                'tenant_id' => null,
                'account_type' => 'system',
                'account_id' => null,
                'amount' => $convertedAmount,
                'amount_currency' => $wallet->currency,
                'business_amount' => $convertedAmount,
                'business_currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeDate,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => 'System offset for wallet debit',
                'created_by' => auth()->id() ?? null,
            ], [
                'tenant_id' => null,
                'account_type' => 'wallet',
                'account_id' => $wallet->id,
                'amount' => $convertedAmount,
                'amount_currency' => $wallet->currency,
                'business_amount' => $convertedAmount,
                'business_currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeDate,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => $note ?? "Debit from wallet available balance",
                'created_by' => auth()->id() ?? null,
            ]);

            event(new WalletDebited($transaction));

            return $transaction;
        });
    }

    public function lockFunds(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null): void
    {
        DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            [$originalAmount, $originalCurrency, $convertedAmount, $convertedCurrency, $exchangeRate, $exchangeDate] =
                $this->exchangeRateService->convertAmount($amount, $currency, $wallet->currency);

            $availableBalance = $wallet->balance - ($wallet->locked_balance ?? 0);

            if ($availableBalance < $convertedAmount) {
                throw new Exception("Insufficient available funds to lock.");
            }

            $wallet->locked_balance = ($wallet->locked_balance ?? 0) + $convertedAmount;
            $wallet->save();
        });
    }

    public function unlockFunds(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null): void
    {
        DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            [$originalAmount, $originalCurrency, $convertedAmount, $convertedCurrency, $exchangeRate, $exchangeDate] =
                $this->exchangeRateService->convertAmount($amount, $currency, $wallet->currency);

            if (($wallet->locked_balance ?? 0) < $convertedAmount) {
                throw new Exception("Amount to unlock exceeds currently locked balance.");
            }

            $wallet->locked_balance -= $convertedAmount;
            $wallet->save();
        });
    }

    public function transferLockedToSpent(Wallet $wallet, float $amount, string $currency, string $type, ?string $reference = null, ?string $note = null)
    {
        return DB::transaction(function () use ($wallet, $amount, $currency, $type, $reference, $note) {
            $wallet = Wallet::lockForUpdate()->find($wallet->id);

            [$originalAmount, $originalCurrency, $convertedAmount, $convertedCurrency, $exchangeRate, $exchangeDate] =
                $this->exchangeRateService->convertAmount($amount, $currency, $wallet->currency);

            if (($wallet->locked_balance ?? 0) < $convertedAmount) {
                throw new Exception("Amount to spend exceeds currently locked balance.");
            }

            // Reduce locked balance
            $wallet->locked_balance -= $convertedAmount;

            // Reduce total balance
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
                'description' => $note ?? "Transfer locked funds to spent",
            ]);

            $wallet->update(['balance' => $balanceAfter]);

             $this->ledgerService->recordTransaction([
                'tenant_id' => null,
                'account_type' => 'system',
                'account_id' => null,
                'amount' => $convertedAmount,
                'amount_currency' => $wallet->currency,
                'business_amount' => $convertedAmount,
                'business_currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeDate,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => 'System offset for locked fund spend',
                'created_by' => auth()->id() ?? null,
            ], [
                'tenant_id' => null,
                'account_type' => 'wallet',
                'account_id' => $wallet->id,
                'amount' => $convertedAmount,
                'amount_currency' => $wallet->currency,
                'business_amount' => $convertedAmount,
                'business_currency' => $wallet->currency,
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeDate,
                'reference_type' => $type,
                'reference_id' => $reference,
                'description' => $note ?? "Transfer locked funds to spent",
                'created_by' => auth()->id() ?? null,
            ]);

            event(new WalletDebited($transaction));

            return $transaction;
        });
    }

    public function getBalance(Wallet $wallet): float
    {
        return (float) $wallet->balance;
    }
}
