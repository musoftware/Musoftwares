<?php

namespace Modules\SmsPaymentGateway\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Services\TransactionIngestionService;
use Modules\SmsPaymentGateway\Services\DeterministicSmsParser;
use Modules\SmsPaymentGateway\Services\RealtimePaymentMatchingEngine;
use Carbon\Carbon;
use App\Models\User;

class TransactionIngestionSpoofTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionIngestionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $parser = new DeterministicSmsParser();
        $matchingEngine = new RealtimePaymentMatchingEngine();
        $this->service = new TransactionIngestionService($parser, $matchingEngine);
    }

    /**
     * Use Reflection to access protected method checkForSpoofing
     */
    protected function invokeCheckForSpoofing($sender, $currentBalance, $currentAmount, $userId)
    {
        $reflection = new \ReflectionClass(get_class($this->service));
        $method = $reflection->getMethod('checkForSpoofing');
        $method->setAccessible(true);
        return $method->invokeArgs($this->service, [$sender, $currentBalance, $currentAmount, $userId]);
    }

    /**
     * @test
     */
    public function it_accepts_valid_balance_progression()
    {
        $user = User::factory()->create();

        // Previous real log: "تم استلام مبلغ 488 جنيه ... رصيدك الحالي 831.49"
        // Let's assume the previous balance before the 488 came in was 343.49
        
        SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $user->id,
            'sender' => 'VF-Cash',
            'amount' => 100.00,
            'balance' => 343.49, 
            'is_spoofed' => false,
            'transaction_date' => Carbon::now()->subMinutes(10)
        ]);

        // New Incoming Transaction (488 EGP, new balance 831.49)
        // 343.49 + 488.00 = 831.49
        $result = $this->invokeCheckForSpoofing('VF-Cash', 831.49, 488.00, $user->id);

        $this->assertFalse($result['is_spoofed']);
        $this->assertNull($result['reason']);
    }

    /**
     * @test
     */
    public function it_detects_spoofed_upward_balance_jump()
    {
        $user = User::factory()->create();

        // From real log: expected 871.49 but received 2616.01 -> Difference 1744.52
        
        SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $user->id,
            'sender' => 'VF-Cash',
            'amount' => 100.00,
            'balance' => 821.49, // Previous Balance
            'is_spoofed' => false,
            'transaction_date' => Carbon::now()->subMinutes(10)
        ]);

        // New Incoming spoofed transaction: (50.00 EGP, but claims balance is 2616.01)
        // Expected: 821.49 + 50.00 = 871.49
        $result = $this->invokeCheckForSpoofing('VF-Cash', 2616.01, 50.00, $user->id);

        $this->assertTrue($result['is_spoofed']);
        $this->assertStringContainsString('Balance mismatch: Expected 871.49', $result['reason']);
        $this->assertStringContainsString('got 2616.01', $result['reason']);
    }

    /**
     * @test
     */
    public function it_ignores_downward_balance_jumps_as_legitimate_external_spending()
    {
        $user = User::factory()->create();

        // Previous balance was 3000 EGP
        SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $user->id,
            'sender' => 'VF-Cash',
            'amount' => 100.00,
            'balance' => 3000.00,
            'is_spoofed' => false,
            'transaction_date' => Carbon::now()->subMinutes(10)
        ]);

        // Merchant withdrew 2500 EGP offline. Balance is now 500.
        // New incoming transaction: 100 EGP. New balance: 600 EGP.
        // Expected if no spend: 3000 + 100 = 3100. Received: 600.
        // 600 < 3000 (Current < Previous), so it should NOT be spoofed.
        
        $result = $this->invokeCheckForSpoofing('VF-Cash', 600.00, 100.00, $user->id);

        $this->assertFalse($result['is_spoofed']);
    }
}
