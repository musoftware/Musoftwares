<?php

namespace Modules\ERP\Tests\Unit;

use Tests\TestCase;
use Modules\ERP\Services\AccountingService;
use Modules\ERP\Models\Accounting\LedgerAccount;
use Modules\ERP\Models\Accounting\JournalEntry;
use Modules\ERP\Models\Accounting\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class AccountingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_balanced_transaction()
    {
        $service = app(AccountingService::class);

        $cashAccount = LedgerAccount::create(['code' => '1000', 'name' => 'Cash', 'type' => 'asset']);
        $revenueAccount = LedgerAccount::create(['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue']);

        $entry = $service->recordTransaction(
            'TEST-REF-1',
            'Test Transaction',
            now()->toDateString(),
            [
                ['ledger_account_id' => $cashAccount->id, 'debit' => 100, 'credit' => 0],
                ['ledger_account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 100],
            ]
        );

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertEquals('TEST-REF-1', $entry->reference);
        $this->assertEquals(2, $entry->lines()->count());

        $this->assertDatabaseHas('journal_entry_lines', [
            'journal_entry_id' => $entry->id,
            'ledger_account_id' => $cashAccount->id,
            'debit' => 100,
            'credit' => 0,
        ]);
    }

    public function test_it_rejects_an_unbalanced_transaction()
    {
        $service = app(AccountingService::class);

        $cashAccount = LedgerAccount::create(['code' => '1000', 'name' => 'Cash', 'type' => 'asset']);
        $revenueAccount = LedgerAccount::create(['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Journal entry must balance.");

        $service->recordTransaction(
            'TEST-REF-2',
            'Test Unbalanced',
            now()->toDateString(),
            [
                ['ledger_account_id' => $cashAccount->id, 'debit' => 100, 'credit' => 0],
                ['ledger_account_id' => $revenueAccount->id, 'debit' => 0, 'credit' => 50],
            ]
        );
    }
}
