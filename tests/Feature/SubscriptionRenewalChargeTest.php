<?php

namespace Tests\Feature;

use App\Console\Commands\RenewSubscriptions;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class SubscriptionRenewalChargeTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    /** config('saas.modules.erp') is 10000 EGP per year, so the monthly base price is 1000 EGP. */
    private const ERP_MONTHLY_EGP = 1000.0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        config(['saas.modules.erp' => self::ERP_MONTHLY_EGP * 10]);
    }

    private function usdUserWithBalance(float $balance): User
    {
        $user = User::factory()->create(['currency_id' => self::USD, 'onboarding_completed' => true]);
        $user->forceFill(['user_balance' => $balance])->save();

        return $user;
    }

    private function expiredErpSubscription(User $user, Carbon $expiresAt): UserSubscription
    {
        return UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => $expiresAt,
            'auto_renew' => true,
        ]);
    }

    public function test_renewal_charges_egp_price_converted_to_wallet_currency(): void
    {
        $user = $this->usdUserWithBalance(50);
        $expiresAt = Carbon::now()->subMinutes(5);
        $subscription = $this->expiredErpSubscription($user, $expiresAt);

        Artisan::call('subscription:renew');

        $expectedUsd = round(self::ERP_MONTHLY_EGP / self::USD_TO_EGP, 2); // 20.00 USD
        $this->assertEquals(round(50 - $expectedUsd, 2), round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(-$expectedUsd, round((float) Transaction::where('user_id', $user->id)->where('type', 'used')->sum('amount'), 2));

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($expiresAt->copy()->addMonth()->toDateTimeString(), $subscription->expires_at->toDateTimeString());
    }

    public function test_renewal_failure_rolls_back_the_charge_and_keeps_subscription(): void
    {
        $user = $this->usdUserWithBalance(50);
        $expiresAt = Carbon::now()->subMinutes(5);
        $subscription = $this->expiredErpSubscription($user, $expiresAt);

        $failingCommand = new class extends RenewSubscriptions
        {
            protected function renewSubscription(UserSubscription $subscription, ?int $proratedDays = null): void
            {
                throw new RuntimeException('Simulated renewal failure after the debit.');
            }
        };
        $this->app[Kernel::class]->registerCommand($failingCommand);

        Artisan::call('subscription:renew');

        $this->assertEquals(50.0, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(0, Transaction::where('user_id', $user->id)->count());

        $subscription->refresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($expiresAt->toDateTimeString(), $subscription->expires_at->toDateTimeString());
    }

    public function test_insufficient_balance_expires_without_charging(): void
    {
        // 0.50 USD cannot pay even one day of a 20 USD month (0.67 USD per day).
        $user = $this->usdUserWithBalance(0.50);
        $subscription = $this->expiredErpSubscription($user, Carbon::now()->subMinutes(5));

        Artisan::call('subscription:renew');

        $this->assertEquals(0.50, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals('expired', $subscription->fresh()->status);
    }
}
