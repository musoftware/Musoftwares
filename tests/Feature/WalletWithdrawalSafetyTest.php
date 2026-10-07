<?php

namespace Tests\Feature;

use App\Exceptions\WithdrawalRejectedException;
use App\Models\PayoutMethod;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPaymentMethod;
use App\Models\UserReferralRequestWithdraw;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class WalletWithdrawalSafetyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
    }

    private function makeEarner(float $walletBalance, float $earnedBalance): User
    {
        $user = User::factory()->create([
            'currency_id' => 1,
            'onboarding_completed' => true,
            'kyc_verified' => true,
        ]);
        $user->forceFill(['user_balance' => $walletBalance, 'pending_commission' => $earnedBalance])->save();

        return $user;
    }

    private function approvedPayoutMethod(User $user): PayoutMethod
    {
        // Known schema mismatch: user_referral_request_withdraws.user_payment_method_id still has a
        // foreign key to the legacy user_payment_methods table, while FinancialController passes a
        // payout_methods id. A legacy row with the same id lets the insert pass the foreign key.
        UserPaymentMethod::create(['user_id' => $user->id, 'type' => 'bank_transfer', 'mobile' => '01000000000']);

        return PayoutMethod::create([
            'user_id' => $user->id,
            'type' => 'bank_transfer',
            'details' => ['iban' => 'EG000000000000000000000000000'],
            'is_default' => true,
            'status' => 'approved',
        ]);
    }

    private function requestWithdrawal(User $user, float $amount, PayoutMethod $method)
    {
        return $this->actingAs($user)->post(route('financial.withdrawals.store'), [
            'amount' => $amount,
            'payout_method_id' => $method->id,
        ]);
    }

    public function test_withdrawal_debits_wallet_and_creates_pending_request(): void
    {
        $user = $this->makeEarner(300, 300);
        $method = $this->approvedPayoutMethod($user);

        $this->requestWithdrawal($user, 200, $method)->assertSessionHasNoErrors();

        $this->assertEquals(100.0, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(1, UserReferralRequestWithdraw::where('user_id', $user->id)->where('status', 'pending')->count());
    }

    public function test_second_sequential_withdrawal_of_the_same_funds_is_rejected(): void
    {
        $user = $this->makeEarner(300, 300);
        $method = $this->approvedPayoutMethod($user);

        $this->requestWithdrawal($user, 200, $method)->assertSessionHasNoErrors();
        $this->requestWithdrawal($user, 200, $method)->assertSessionHasErrors('amount');

        $this->assertEquals(100.0, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(1, UserReferralRequestWithdraw::where('user_id', $user->id)->count());
    }

    public function test_withdrawal_cannot_exceed_wallet_balance_even_with_large_earned_balance(): void
    {
        $user = $this->makeEarner(100, 1000);
        $method = $this->approvedPayoutMethod($user);

        $this->requestWithdrawal($user, 200, $method)->assertSessionHasErrors('amount');

        $this->assertEquals(100.0, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(0, UserReferralRequestWithdraw::where('user_id', $user->id)->count());
        $this->assertEquals(0, Transaction::where('user_id', $user->id)->count());
    }

    public function test_service_rechecks_balance_under_lock_with_a_stale_user_instance(): void
    {
        $user = $this->makeEarner(300, 300);
        $method = $this->approvedPayoutMethod($user);
        $service = app(BalanceService::class);

        // The same stale model is reused, like two parallel requests that loaded the user at once.
        $service->processWithdrawalRequest($user, 200, $method->id);

        $this->expectException(WithdrawalRejectedException::class);
        try {
            $service->processWithdrawalRequest($user, 200, $method->id);
        } finally {
            $this->assertEquals(100.0, round((float) $user->fresh()->user_balance, 2));
            $this->assertEquals(1, UserReferralRequestWithdraw::where('user_id', $user->id)->count());
        }
    }
}
