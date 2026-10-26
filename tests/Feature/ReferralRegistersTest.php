<?php

namespace Tests\Feature;

use App\Models\Earning;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

/**
 * /referrals/registers crashed with "No exchange rate found from currency 0": it read the old
 * earnings.currency column, which is 0 on rows written after the rename to currency_id.
 */
class ReferralRegistersTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    public function test_commissions_are_converted_from_the_earning_currency_id(): void
    {
        $this->seedUsdEgpRates();
        $referrer = User::factory()->create(['currency_id' => self::EGP, 'email_verified_at' => now()]);
        $referred = User::factory()->create(['ref_user_id' => $referrer->id, 'email_verified_at' => now()]);

        $earning = Earning::create(['user_id' => $referrer->id, 'referred_user_id' => $referred->id, 'amount' => 100, 'currency_id' => self::EGP]);
        if (Schema::hasColumn('earnings', 'currency')) {
            DB::table('earnings')->where('id', $earning->id)->update(['currency' => 0]); // what production rows hold
        }

        $this->actingAs($referrer)->get('/referrals/registers')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Client/Dashboard/Referrals/Registers')
            ->where('global_commission_total', fn ($total) => (float) $total > 0)
        );
    }

    public function test_source_currency_falls_back_from_the_new_column_to_the_old_one(): void
    {
        $earning = new Earning;
        $earning->setRawAttributes(['currency_id' => null, 'currency' => self::EGP]);
        $this->assertSame(self::EGP, $earning->sourceCurrencyId());

        $earning->setRawAttributes(['currency_id' => self::USD, 'currency' => 0]);
        $this->assertSame(self::USD, $earning->sourceCurrencyId());
    }
}
