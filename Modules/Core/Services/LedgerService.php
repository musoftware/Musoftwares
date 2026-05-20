<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerService
{
    /**
     * Record a double-entry transaction between two accounts.
     * Maps the flexible line parameters from WalletService to standard journal entries.
     */
    public function recordTransaction(array $line1, array $line2): void
    {
        DB::transaction(function () use ($line1, $line2) {
            // 1. Resolve or create ledgers
            $ledger1 = $this->resolveLedger($line1['account_type']);
            $ledger2 = $this->resolveLedger($line2['account_type']);

            // 2. Resolve or create accounts
            $account1 = $this->resolveAccount($ledger1->id, $line1['account_type'], $line1['account_id'], $line1['amount_currency']);
            $account2 = $this->resolveAccount($ledger2->id, $line2['account_type'], $line2['account_id'], $line2['amount_currency']);

            // 3. Create journal entry
            $journalEntryId = Str::uuid()->toString();
            $description = $line1['description'] ?? 'Ledger transaction';
            $referenceType = $line1['reference_type'] ?? null;
            $referenceId = $line1['reference_id'] ?? null;
            $date = $line1['exchange_rate_date'] ?? now()->toDateString();

            DB::table('journal_entries')->insert([
                'id' => $journalEntryId,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'date' => $date,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Create journal entry lines
            // We assume:
            // Line 1 is the main action. We determine Debit vs Credit based on amount or transaction context.
            // In WalletService.php, creditAvailable does:
            // - line1: 'account_type' => 'wallet', 'amount' => $convertedAmount (main action)
            // - line2: 'account_type' => 'system', 'amount' => $convertedAmount (offset)
            //
            // In debitAvailable does:
            // - line1: 'account_type' => 'system', 'amount' => $convertedAmount (offset)
            // - line2: 'account_type' => 'wallet', 'amount' => $convertedAmount (main action)

            $debit1 = 0;
            $credit1 = 0;
            $debit2 = 0;
            $credit2 = 0;

            if ($line1['account_type'] === 'wallet') {
                // Wallet increase: Debit Wallet (asset increases on debit), Credit System (system offset)
                $debit1 = (float) $line1['amount'];
                $credit2 = (float) $line2['amount'];
            } else {
                // Wallet decrease: Debit System (system offset), Credit Wallet (asset decreases on credit)
                $debit1 = (float) $line1['amount'];
                $credit2 = (float) $line2['amount'];
            }

            // Insert line 1
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalEntryId,
                'account_id' => $account1->id,
                'debit' => $debit1,
                'credit' => $credit1,
                'amount' => $line1['amount'],
                'amount_currency' => $line1['amount_currency'],
                'business_amount' => $line1['business_amount'],
                'business_currency' => $line1['business_currency'],
                'exchange_rate' => $line1['exchange_rate'],
                'exchange_rate_date' => $line1['exchange_rate_date'] ?? now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert line 2
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalEntryId,
                'account_id' => $account2->id,
                'debit' => $debit2,
                'credit' => $credit2,
                'amount' => $line2['amount'],
                'amount_currency' => $line2['amount_currency'],
                'business_amount' => $line2['business_amount'],
                'business_currency' => $line2['business_currency'],
                'exchange_rate' => $line2['exchange_rate'],
                'exchange_rate_date' => $line2['exchange_rate_date'] ?? now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. Update account balances
            $this->updateAccountBalance($account1->id, $debit1, $credit1);
            $this->updateAccountBalance($account2->id, $debit2, $credit2);
        });
    }

    protected function resolveLedger(string $type)
    {
        $name = ucfirst($type) . ' Ledger';
        $ledgerType = ($type === 'wallet') ? 'asset' : 'expense';

        $ledgerId = DB::table('ledgers')->where('name', $name)->value('id');

        if (!$ledgerId) {
            $ledgerId = DB::table('ledgers')->insertGetId([
                'name' => $name,
                'type' => $ledgerType,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return DB::table('ledgers')->where('id', $ledgerId)->first();
    }

    protected function resolveAccount(int $ledgerId, string $type, ?int $accountId, string $currency)
    {
        $code = strtoupper($type) . '-' . ($accountId ?? 'SYSTEM');
        $name = ucfirst($type) . ' Account' . ($accountId ? ' #' . $accountId : '');

        $account = DB::table('accounts')->where('code', $code)->first();

        if (!$account) {
            $id = DB::table('accounts')->insertGetId([
                'ledger_id' => $ledgerId,
                'name' => $name,
                'code' => $code,
                'balance' => 0,
                'currency_code' => $currency,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $account = DB::table('accounts')->where('id', $id)->first();
        }

        return $account;
    }

    protected function updateAccountBalance(int $accountId, float $debit, float $credit): void
    {
        $account = DB::table('accounts')->where('id', $accountId)->lockForUpdate()->first();
        $ledger = DB::table('ledgers')->where('id', $account->ledger_id)->first();

        $newBalance = (float) $account->balance;

        if (in_array($ledger->type, ['asset', 'expense'])) {
            $newBalance += ($debit - $credit);
        } else {
            $newBalance += ($credit - $debit);
        }

        DB::table('accounts')->where('id', $accountId)->update([
            'balance' => $newBalance,
            'updated_at' => now()
        ]);
    }
}
