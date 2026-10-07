<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WalletTransfer;
use App\Services\WalletTransferService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class WalletTransferSafetyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private WalletTransferService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-15 12:00:00'));
        $this->seedUsdEgpRates();
        $this->service = app(WalletTransferService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function walletUser(float $balance): User
    {
        $user = User::factory()->create(['currency_id' => self::USD]);
        $user->forceFill(['user_balance' => $balance])->save();

        return $user;
    }

    private function recordCompletedTransfer(User $sender, User $receiver, float $amount, Carbon $at): void
    {
        $transfer = WalletTransfer::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'amount' => $amount,
            'fee_amount' => 0,
            'currency' => 'USD',
            'exchange_rate' => 1,
            'converted_amount' => $amount,
            'converted_currency' => 'USD',
            'status' => WalletTransfer::STATUS_COMPLETED,
            'processed_at' => $at,
        ]);
        $transfer->forceFill(['created_at' => $at])->save();
    }

    private function assertTransferRejected(callable $transfer, string $messagePart): void
    {
        try {
            $transfer();
            $this->fail('Transfer should have been rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString($messagePart, implode(' ', $e->errors()['amount'] ?? []));
        }
    }

    public function test_transfer_moves_amount_and_fee(): void
    {
        $sender = $this->walletUser(100);
        $receiver = $this->walletUser(0);

        $this->service->executeTransfer($sender->id, $receiver->id, 50, self::USD);

        // 1% of 50 is below the $0.50 minimum fee.
        $this->assertEquals(49.50, round((float) $sender->fresh()->user_balance, 2));
        $this->assertEquals(50.00, round((float) $receiver->fresh()->user_balance, 2));
    }

    public function test_second_transfer_that_exceeds_remaining_balance_is_rejected(): void
    {
        $sender = $this->walletUser(100);
        $receiver = $this->walletUser(0);

        $this->service->executeTransfer($sender->id, $receiver->id, 50, self::USD);
        $this->assertTransferRejected(
            fn () => $this->service->executeTransfer($sender->id, $receiver->id, 60, self::USD),
            'Insufficient funds'
        );

        $this->assertEquals(49.50, round((float) $sender->fresh()->user_balance, 2));
        $this->assertEquals(1, WalletTransfer::where('sender_id', $sender->id)->count());
    }

    public function test_daily_limit_is_enforced(): void
    {
        $sender = $this->walletUser(100000);
        $receiver = $this->walletUser(0);
        $this->recordCompletedTransfer($sender, $receiver, 49990, Carbon::now()->subHour());

        $this->assertTransferRejected(
            fn () => $this->service->executeTransfer($sender->id, $receiver->id, 20, self::USD),
            'Daily transfer limit exceeded'
        );

        $this->assertEquals(100000.00, round((float) $sender->fresh()->user_balance, 2));
    }

    public function test_monthly_limit_is_enforced(): void
    {
        $sender = $this->walletUser(100000);
        $receiver = $this->walletUser(0);
        $this->recordCompletedTransfer($sender, $receiver, 499990, Carbon::now()->subDays(5));

        $this->assertTransferRejected(
            fn () => $this->service->executeTransfer($sender->id, $receiver->id, 20, self::USD),
            'Monthly transfer limit exceeded'
        );

        $this->assertEquals(100000.00, round((float) $sender->fresh()->user_balance, 2));
    }
}
