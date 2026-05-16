<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use Modules\Core\Models\WalletTransaction;

class FinancialTransactionService
{
    /**
     * Create a double-entry journal record.
     */
    public function recordJournalEntry(
        int $debitAccountId,
        int $creditAccountId,
        float $amount,
        string $currencyCode,
        string $description,
        string $referenceType = null,
        string $referenceId = null,
        float $exchangeRate = 1.0,
        string $exchangeRateDate = null
    ): void {
        DB::transaction(function () use ($debitAccountId, $creditAccountId, $amount, $currencyCode, $description, $referenceType, $referenceId, $exchangeRate, $exchangeRateDate) {

            $journalEntryId = Str::uuid();
            $date = now()->toDateString();
            $exchangeRateDate = $exchangeRateDate ?? $date;

            // Business amount is unified (base) currency amount, e.g. USD
            $businessAmount = $amount * $exchangeRate;

            DB::table('journal_entries')->insert([
                'id' => $journalEntryId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Debit Line
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalEntryId,
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
                'amount' => $amount,
                'amount_currency' => $currencyCode,
                'business_amount' => $businessAmount,
                'business_currency' => 'USD', // Base currency assumption
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeRateDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Credit Line
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalEntryId,
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
                'amount' => $amount,
                'amount_currency' => $currencyCode,
                'business_amount' => $businessAmount,
                'business_currency' => 'USD',
                'exchange_rate' => $exchangeRate,
                'exchange_rate_date' => $exchangeRateDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update Account Balances
            $this->updateAccountBalance($debitAccountId, $amount, 'debit');
            $this->updateAccountBalance($creditAccountId, $amount, 'credit');
        });
    }

    protected function updateAccountBalance(int $accountId, float $amount, string $type)
    {
        // This locks the account row for update
        $account = DB::table('accounts')->where('id', $accountId)->lockForUpdate()->first();
        $ledger = DB::table('ledgers')->where('id', $account->ledger_id)->first();

        $newBalance = $account->balance;

        // Asset/Expense accounts increase with Debit
        // Liability/Equity/Revenue accounts increase with Credit
        if (in_array($ledger->type, ['asset', 'expense'])) {
            $newBalance += ($type === 'debit') ? $amount : -$amount;
        } else {
            $newBalance += ($type === 'credit') ? $amount : -$amount;
        }

        DB::table('accounts')->where('id', $accountId)->update(['balance' => $newBalance, 'updated_at' => now()]);
    }

    /**
     * Transfer funds between two user wallets
     */
    public function transferWalletFunds(
        int $sourceWalletId,
        int $destinationWalletId,
        float $amount,
        string $description,
        string $referenceType = null,
        string $referenceId = null
    ): void {
        DB::transaction(function () use ($sourceWalletId, $destinationWalletId, $amount, $description, $referenceType, $referenceId) {

            $sourceWallet = DB::table('wallets')->where('id', $sourceWalletId)->lockForUpdate()->first();
            $destWallet = DB::table('wallets')->where('id', $destinationWalletId)->lockForUpdate()->first();

            if ($sourceWallet->balance < $amount) {
                throw new \Exception("Insufficient funds.");
            }

            // Debit Source
            $newSourceBalance = $sourceWallet->balance - $amount;
            $debitTxId = DB::table('wallet_transactions')->insertGetId([
                'wallet_id' => $sourceWalletId,
                'type' => 'debit',
                'amount' => $amount,
                'balance_before' => $sourceWallet->balance,
                'balance_after' => $newSourceBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('wallets')->where('id', $sourceWalletId)->update(['balance' => $newSourceBalance, 'updated_at' => now()]);

            $debitTx = WalletTransaction::find($debitTxId);
            event(new WalletDebited($debitTx));

            // Credit Destination
            $newDestBalance = $destWallet->balance + $amount;
            $creditTxId = DB::table('wallet_transactions')->insertGetId([
                'wallet_id' => $destinationWalletId,
                'type' => 'credit',
                'amount' => $amount,
                'balance_before' => $destWallet->balance,
                'balance_after' => $newDestBalance,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('wallets')->where('id', $destinationWalletId)->update(['balance' => $newDestBalance, 'updated_at' => now()]);

            $creditTx = WalletTransaction::find($creditTxId);
            event(new WalletCredited($creditTx));
        });
    }
}
