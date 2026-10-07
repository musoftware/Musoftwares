<?php

namespace Tests\Feature;

use App\Models\Earning;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class EarningsClearingTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
    }

    private function maturedEarning(User $user, float $amount): Earning
    {
        return Earning::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency_id' => self::USD,
            'convert_to_balance_on' => now()->subDays(2)->toDateString(),
        ]);
    }

    public function test_running_clearing_twice_credits_each_earning_once(): void
    {
        $user = User::factory()->create(['currency_id' => self::USD]);
        $earning = $this->maturedEarning($user, 40);

        Artisan::call('earnings:clear');
        Artisan::call('earnings:clear');

        $this->assertEquals(40.0, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(1, Transaction::where('user_id', $user->id)->where('type', 'earned')->count());
        $this->assertNotNull($earning->fresh()->transaction_id);
    }

    public function test_already_cleared_earning_is_skipped(): void
    {
        $user = User::factory()->create(['currency_id' => self::USD]);
        $first = $this->maturedEarning($user, 40);
        Artisan::call('earnings:clear');

        // A second matured earning in the same run must still be credited; the cleared one must not.
        $this->maturedEarning($user, 10);
        Artisan::call('earnings:clear');

        $this->assertEquals(50.0, round((float) $user->fresh()->user_balance, 2));
        $this->assertEquals(2, Transaction::where('user_id', $user->id)->where('type', 'earned')->count());
        $this->assertEquals(1, Transaction::where('id', $first->fresh()->transaction_id)->count());
    }

    public function test_future_earning_is_not_credited(): void
    {
        $user = User::factory()->create(['currency_id' => self::USD]);
        Earning::create([
            'user_id' => $user->id,
            'amount' => 25,
            'currency_id' => self::USD,
            'convert_to_balance_on' => now()->addDays(5)->toDateString(),
        ]);

        Artisan::call('earnings:clear');

        $this->assertEquals(0.0, round((float) $user->fresh()->user_balance, 2));
    }
}
