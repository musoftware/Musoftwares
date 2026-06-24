<?php

namespace Modules\ERP\Services;

use Modules\ERP\Models\Accounting\JournalEntry;
use Modules\ERP\Models\Accounting\JournalEntryLine;
use Modules\ERP\Models\Accounting\LedgerAccount;
use Modules\ERP\Infrastructure\Context\TenantContext;
use Illuminate\Support\Facades\DB;
use Exception;

class AccountingService
{
    /**
     * Record a double-entry journal entry.
     *
     * @param string $reference
     * @param string $description
     * @param string $date
     * @param array $lines Array of ['ledger_account_id' => int, 'debit' => float, 'credit' => float]
     * @param int|null $tenantId
     * @return JournalEntry
     * @throws Exception
     */
    public function recordTransaction(string $reference, string $description, string $date, array $lines, ?int $tenantId = null)
    {
        if (empty($tenantId)) {
            $tenantId = app(TenantContext::class)->getTenantId();
        }

        if (!$tenantId && session()->has('tenant_id')) {
            $tenantId = session('tenant_id');
        }

        if (!$tenantId && auth('erp_team')->check()) {
            $tenantId = auth('erp_team')->user()->tenant_id;
        }

        if (!$tenantId) {
            throw new Exception('Tenant ID is required for recording an accounting transaction.');
        }

        DB::beginTransaction();

        try {
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $totalDebit += (float) ($line['debit'] ?? 0);
                $totalCredit += (float) ($line['credit'] ?? 0);
            }

            // Ensure debits equal credits
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new Exception("Journal entry must balance. Debits: {$totalDebit}, Credits: {$totalCredit}");
            }

            if ($totalDebit == 0) {
                throw new Exception("Transaction must have a non-zero value.");
            }

            $journalEntry = JournalEntry::create([
                'tenant_id' => $tenantId,
                'reference' => $reference,
                'description' => $description,
                'entry_date' => $date,
            ]);

            foreach ($lines as $line) {
                $journalEntry->lines()->create([
                    'tenant_id' => $tenantId,
                    'ledger_account_id' => $line['ledger_account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            DB::commit();

            return $journalEntry;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Helper to retrieve or create standard ledger accounts for a tenant.
     */
    public function getOrCreateAccount(int $tenantId, string $code, string $name, string $type): LedgerAccount
    {
        return LedgerAccount::firstOrCreate([
            'tenant_id' => $tenantId,
            'code' => $code,
        ], [
            'name' => $name,
            'type' => $type,
        ]);
    }
}
