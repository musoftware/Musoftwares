<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

/**
 * The referred-people list used to send each referred user's full model (e-mail, phone, balances)
 * to the referrer. It now sends only the name, a masked e-mail and the dates.
 */
class ReferralPrivacyTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    public function test_referrer_sees_masked_emails_and_nothing_else_about_referred_users(): void
    {
        $this->seedUsdEgpRates();
        $referrer = User::factory()->create(['currency_id' => self::EGP, 'email_verified_at' => now()]);
        User::factory()->create([
            'ref_user_id' => $referrer->id,
            'email_verified_at' => now(),
            'name' => 'Mona Ali',
            'email' => 'mona.ali@gmail.com',
            'phone_number' => '+201000000123',
        ]);

        $response = $this->actingAs($referrer)->get('/referrals/registers');
        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Client/Dashboard/Referrals/Registers')
            ->where('referred_users.data.0.name', 'Mona Ali')
            ->where('referred_users.data.0.email', 'm***@gmail.com')
            ->missing('referred_users.data.0.phone_number')
            ->missing('referred_users.data.0.user_balance')
        );

        $props = json_encode($response->viewData('page')['props']['referred_users']);
        $this->assertStringNotContainsString('mona.ali@gmail.com', $props);
        $this->assertStringNotContainsString('+201000000123', $props);
    }

    public function test_referral_pages_render(): void
    {
        $this->seedUsdEgpRates();
        $user = User::factory()->create(['currency_id' => self::EGP, 'email_verified_at' => now()]);

        $this->actingAs($user)->get('/referrals')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Client/Dashboard/Referrals/Index'));
        $this->actingAs($user)->get('/referrals/earnings')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Client/Dashboard/Referrals/Earns'));
        $this->actingAs($user)->get('/vouchers')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Client/Vouchers/Index'));
    }
}
