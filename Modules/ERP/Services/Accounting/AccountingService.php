<?php

namespace Modules\ERP\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Modules\ERP\Models\Accounting\JournalEntry;
use Modules\ERP\Models\Accounting\JournalEntryLine;
use Modules\ERP\Models\Accounting\AccountingRule;
use Illuminate\Database\Eloquent\Model;
use Exception;

class AccountingService
{
    /**
     * Generate a double-entry journal from a source document using defined Accounting Rules.
     *
     * @param int $tenantId
     * @param string $eventName
     * @param Model $sourceDocument
     * @param float $amount
     * @param string $currencyCode
     * @param float $exchangeRate
     * @param string $description
     * @return JournalEntry
     * @throws Exception
     */
    public function generateAutomatedEntry(
        int $tenantId,
        string $eventName,
        Model $sourceDocument,
        float $amount,
        string $currencyCode = 'USD',
        float $exchangeRate = 1.0,
        string $description = ''
    ): JournalEntry {
        return DB::transaction(function () use (
            $tenantId, $eventName, $sourceDocument, $amount, $currencyCode, $exchangeRate, $description
        ) {
            $rule = AccountingRule::where('tenant_id', $tenantId)
                ->where('event_name', $eventName)
                ->first();

            if (!$rule) {
                throw new Exception("Accounting Rule for event '{$eventName}' not found for tenant.");
            }

            // Create Journal Entry (Draft mode for review)
            $entry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'entry_number' => $this->generateEntryNumber($tenantId),
                'description' => $description,
                'entry_date' => now()->toDateString(),
                'currency_code' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'status' => 'draft', // User requested draft/manual review option, or based on preference
                'document_type' => get_class($sourceDocument),
                'document_id' => $sourceDocument->getKey(),
            ]);

            $baseAmount = $amount * $exchangeRate;

            // Debit Line
            $entry->lines()->create([
                'tenant_id' => $tenantId,
                'chart_of_account_id' => $rule->debit_account_id,
                'description' => "Debit for {$eventName}",
                'debit' => $amount,
                'credit' => 0,
                'base_debit' => $baseAmount,
                'base_credit' => 0,
            ]);

            // Credit Line
            $entry->lines()->create([
                'tenant_id' => $tenantId,
                'chart_of_account_id' => $rule->credit_account_id,
                'description' => "Credit for {$eventName}",
                'debit' => 0,
                'credit' => $amount,
                'base_debit' => 0,
                'base_credit' => $baseAmount,
            ]);

            return $entry;
        });
    }

    /**
     * Post a draft Journal Entry to the Ledger.
     *
     * @param JournalEntry $entry
     * @return bool
     * @throws Exception
     */
    public function postEntry(JournalEntry $entry): bool
    {
        if ($entry->status === 'posted') {
            throw new Exception("Journal Entry is already posted.");
        }

        // Validate debits equal credits
        $totalDebit = $entry->lines()->sum('base_debit');
        $totalCredit = $entry->lines()->sum('base_credit');

        if (round($totalDebit, 4) !== round($totalCredit, 4)) {
            throw new Exception("Journal Entry is unbalanced. Debits: {$totalDebit}, Credits: {$totalCredit}");
        }

        // Optional: Check if Accounting Period is open, etc.

        $entry->update(['status' => 'posted']);

        // Here we could update account balances dynamically if a Balance table exists

        return true;
    }

    private function generateEntryNumber(int $tenantId): string
    {
        // Simple entry number generation. Could be improved with sequence tables.
        $lastEntry = JournalEntry::where('tenant_id', $tenantId)->latest('id')->first();
        $nextId = $lastEntry ? ((int) str_replace('JE-', '', $lastEntry->entry_number)) + 1 : 1;
        return 'JE-' . str_pad((string)$nextId, 6, '0', STR_PAD_LEFT);
    }
}
