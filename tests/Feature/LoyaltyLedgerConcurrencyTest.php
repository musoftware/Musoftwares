<?php

namespace Tests\Feature;

use App\Models\LoyaltyPointTransaction;
use App\Models\LoyaltyRedemption;
use App\Models\LoyaltyReward;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class LoyaltyLedgerConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private LoyaltyService $loyalty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loyalty = app(LoyaltyService::class);
    }

    public function test_redeem_with_stale_user_model_cannot_drive_balance_negative(): void
    {
        $user = $this->userWithBalance(300);
        $staleCopy = User::find($user->id); // simulates a second request that loaded 300 points earlier
        $reward = $this->reward(200);

        $this->loyalty->redeemReward($user, $reward);
        $this->assertSame(100, (int) $user->fresh()->loyalty_points_balance);

        try {
            $this->loyalty->redeemReward($staleCopy, $reward);
            $this->fail('Second redemption should have been rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Insufficient loyalty points balance.', $e->getMessage());
        }

        $this->assertSame(100, (int) $user->fresh()->loyalty_points_balance);
        $this->assertSame(1, LoyaltyRedemption::where('user_id', $user->id)->count());
        $this->assertSame(100, (int) LoyaltyPointTransaction::where('user_id', $user->id)
            ->where('event_type', 'reward_redemption')->value('balance_after'));
    }

    public function test_manual_adjustment_uses_current_balance_not_stale_model(): void
    {
        $user = $this->userWithBalance(100);
        $staleCopy = User::find($user->id);
        $user->forceFill(['loyalty_points_balance' => 400])->saveQuietly();

        $txn = $this->loyalty->adjustPointsManually($staleCopy, 50, 'Courtesy');

        $this->assertSame(450, (int) $txn->balance_after);
        $this->assertSame(450, (int) $user->fresh()->loyalty_points_balance);
    }

    public function test_quarterly_expiry_runs_once_per_quarter(): void
    {
        $user = $this->userWithBalance(120);

        $this->assertSame(1, $this->loyalty->expireQuarterlyPoints('Q3 2026'));
        $user->forceFill(['loyalty_points_balance' => 30])->saveQuietly(); // points earned after the run
        $this->assertSame(0, $this->loyalty->expireQuarterlyPoints('Q3 2026'));

        $this->assertSame(30, (int) $user->fresh()->loyalty_points_balance);
        $this->assertSame(1, LoyaltyPointTransaction::where('user_id', $user->id)
            ->where('event_type', 'points_quarterly_expired')->count());
    }

    private function userWithBalance(int $points): User
    {
        $user = User::factory()->create();
        $user->forceFill(['loyalty_points_balance' => $points])->saveQuietly();

        return $user->fresh();
    }

    private function reward(int $cost): LoyaltyReward
    {
        return LoyaltyReward::create([
            'name' => 'Test reward',
            'reward_type' => 'invoice_discount',
            'points_cost' => $cost,
            'discount_value' => 10.00,
            'discount_type' => 'percentage',
            'is_active' => true,
        ]);
    }
}
