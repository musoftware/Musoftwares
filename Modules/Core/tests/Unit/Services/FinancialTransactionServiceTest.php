<?php

namespace Modules\Core\Tests\Unit\Services;

use Tests\TestCase;
use Modules\Core\Services\FinancialTransactionService;
use Illuminate\Support\Facades\Event;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\WalletTransaction;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\PreserveGlobalState;

class FinancialTransactionServiceTest extends TestCase
{
    protected FinancialTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FinancialTransactionService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_record_journal_entry(): void
    {
        $dbMock = Mockery::mock();
        DB::swap($dbMock);

        $dbMock->shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $dbMock->shouldReceive('table')->with('journal_entries')->andReturnSelf();
        $dbMock->shouldReceive('insert')->once();

        $dbMock->shouldReceive('table')->with('journal_entry_lines')->andReturnSelf();
        $dbMock->shouldReceive('insert')->twice();

        $dbMock->shouldReceive('table')->with('accounts')->andReturnSelf();

        // Setup expectations for when where('id', 1) is called (Debit Account)
        $dbMock->shouldReceive('where')->with('id', 1)->andReturnSelf();

        // Setup expectations for when where('id', 2) is called (Credit Account)
        $dbMock->shouldReceive('where')->with('id', 2)->andReturnSelf();

        $dbMock->shouldReceive('lockForUpdate')->andReturnSelf();

        // We'll mock the first() to return different things depending on which account is being fetched.
        // A better approach in Mockery for a chain is tricky, but let's just make it return an object with both.
        // Wait, updateAccountBalance is called twice:
        // 1. $this->updateAccountBalance($debitAccountId, $amount, 'debit');
        // 2. $this->updateAccountBalance($creditAccountId, $amount, 'credit');
        // In updateAccountBalance:
        // $account = DB::table('accounts')->where('id', $accountId)->lockForUpdate()->first();
        // $ledger = DB::table('ledgers')->where('id', $account->ledger_id)->first();
        // The first call is for id=1, ledger_id=1. The second call is for id=2, ledger_id=2.

        $dbMock->shouldReceive('first')->andReturn(
            (object)['ledger_id' => 1, 'balance' => 0], // First call to updateAccountBalance (for account)
            (object)['type' => 'asset'], // First call to updateAccountBalance (for ledger)
            (object)['ledger_id' => 2, 'balance' => 0], // Second call to updateAccountBalance (for account)
            (object)['type' => 'revenue'] // Second call to updateAccountBalance (for ledger)
        );

        $dbMock->shouldReceive('update')->twice();

        $dbMock->shouldReceive('table')->with('ledgers')->andReturnSelf();
        // No need to mock specific where('id', ...) if we just use the chain return above

        $this->service->recordJournalEntry(
            1,
            2,
            100.50,
            'USD',
            'Test Sale'
        );

        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_transfer_wallet_funds_success(): void
    {
        Event::fake();

        $dbMock = Mockery::mock();
        DB::swap($dbMock);

        $dbMock->shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $dbMock->shouldReceive('table')->with('wallets')->andReturnSelf();
        $dbMock->shouldReceive('where')->with('id', Mockery::any())->andReturnSelf();
        $dbMock->shouldReceive('lockForUpdate')->andReturnSelf();
        $dbMock->shouldReceive('first')->andReturn(
            (object)['balance' => 500.00],
            (object)['balance' => 100.00]
        );
        $dbMock->shouldReceive('update')->twice();

        $dbMock->shouldReceive('table')->with('wallet_transactions')->andReturnSelf();
        $dbMock->shouldReceive('insertGetId')->twice()->andReturn(1, 2);

        $walletTxMock = Mockery::mock('alias:'.WalletTransaction::class);
        $walletTxMock->shouldReceive('find')->with(1)->andReturn(new WalletTransaction());
        $walletTxMock->shouldReceive('find')->with(2)->andReturn(new WalletTransaction());

        $this->service->transferWalletFunds(
            1,
            2,
            200.00,
            'Test Transfer'
        );

        Event::assertDispatched(WalletDebited::class);
        Event::assertDispatched(WalletCredited::class);
        $this->assertTrue(true);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_transfer_wallet_funds_insufficient_funds(): void
    {
        $dbMock = Mockery::mock();
        DB::swap($dbMock);

        $dbMock->shouldReceive('transaction')->once()->andReturnUsing(function ($callback) {
            return $callback();
        });

        $dbMock->shouldReceive('table')->with('wallets')->andReturnSelf();
        $dbMock->shouldReceive('where')->with('id', Mockery::any())->andReturnSelf();
        $dbMock->shouldReceive('lockForUpdate')->andReturnSelf();
        $dbMock->shouldReceive('first')->andReturn(
            (object)['balance' => 50.00],
            (object)['balance' => 100.00]
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient funds.");

        $this->service->transferWalletFunds(
            1,
            2,
            200.00,
            'Test Transfer'
        );
    }
}
