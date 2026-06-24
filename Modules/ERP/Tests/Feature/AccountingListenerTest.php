<?php

namespace Modules\ERP\Tests\Feature;

use Tests\TestCase;
use App\Events\WalletCredited;
use App\Models\Wallet;
use Modules\ERP\Models\Accounting\JournalEntry;
use Modules\ERP\Models\Accounting\LedgerAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class AccountingListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_wallet_credited_event_generates_journal_entry()
    {
        $tenant = \Modules\ERP\Models\Tenant::create(['name' => 'Test Tenant', 'domain' => 'test']);
        session(['tenant_id' => $tenant->id]);

        $wallet = new Wallet();
        $wallet->id = 1;

        // Dispatch the event which should trigger the AccountingListener
        event(new WalletCredited($wallet, 50.00, 1));

        $this->assertDatabaseHas('ledger_accounts', [
            'tenant_id' => $tenant->id,
            'code' => '1000',
            'name' => 'Cash/Bank',
        ]);

        $this->assertDatabaseHas('ledger_accounts', [
            'tenant_id' => $tenant->id,
            'code' => '2000',
            'name' => 'Wallet Balances',
        ]);

        $entry = JournalEntry::where('reference', 'like', 'WAL-CREDIT-%')->first();
        $this->assertNotNull($entry);
        $this->assertEquals($tenant->id, $entry->tenant_id);

        $this->assertEquals(2, $entry->lines()->count());
        $this->assertEquals(50.00, $entry->lines()->where('debit', '>', 0)->first()->debit);
        $this->assertEquals(50.00, $entry->lines()->where('credit', '>', 0)->first()->credit);
    }
}
