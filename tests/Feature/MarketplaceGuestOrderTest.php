<?php

namespace Tests\Feature;

use App\Mail\MarketplaceAccountCreatedMail;
use App\Models\Currency;
use App\Models\User;
use App\Models\UserReferral;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Marketplace\Enums\EscrowStatus;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Services\EscrowService;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class MarketplaceGuestOrderTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private User $seller;

    private ServicePackage $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        Mail::fake();
        config(['services.marketplace_guest_orders.limiter_store' => 'array']);
        Cache::store('array')->flush();

        Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']);
        $this->seller = User::factory()->create(['user_balance' => 0, 'currency_id' => 1]);
        $category = ServiceCategory::create(['name' => 'Tools', 'slug' => 'tools']);
        $service = Service::create([
            'seller_id' => $this->seller->id, 'title' => 'Bulk tool', 'slug' => 'bulk-tool', 'category_id' => $category->id,
            'description' => 'Bulk tool', 'status' => 'active', 'referral_commission_from' => 'seller', 'referral_commission_percentage' => 10,
        ]);
        $this->package = ServicePackage::create(['service_id' => $service->id, 'name' => 'Basic', 'description' => 'Basic', 'price' => 100, 'currency_id' => 1, 'delivery_days' => 3]);
    }

    private function order(array $overrides = [])
    {
        return $this->post('/marketplace/orders/guest', $overrides + [
            'package_id' => $this->package->id, 'name' => 'New Buyer', 'email' => 'NEW@example.com', 'privacy_consent' => '1',
        ]);
    }

    private function referral(User $marketer): UserReferral
    {
        return UserReferral::create(['user_id' => $marketer->id, 'title' => 'Main', 'key' => 'MARKET1', 'slug' => 'market1']);
    }

    private function completedOrder(User $buyer): MarketplaceEscrow
    {
        $order = ServiceOrder::create(['buyer_id' => $buyer->id, 'seller_id' => $this->seller->id, 'package_id' => $this->package->id, 'amount' => 100, 'commission_amount' => 10, 'currency_id' => 1, 'status' => 'completed']);

        return MarketplaceEscrow::create(['order_id' => $order->id, 'amount' => 100, 'currency_id' => 1, 'business_amount' => 100, 'business_currency_id' => 1, 'exchange_rate' => 1, 'exchange_rate_date' => now(), 'status' => EscrowStatus::HELD]);
    }

    public function test_a_guest_order_opens_an_account_and_goes_to_payment(): void
    {
        $response = $this->order(['phone' => '01012345678']);

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('01012345678', $user->mobile_1);
        $this->assertStringContainsString('kashier', strtolower($response->headers->get('Location')));
        $response->assertSessionHas('marketplace.pending_package_id', $this->package->id);
        Mail::assertSent(MarketplaceAccountCreatedMail::class, fn ($mail) => $mail->hasTo('new@example.com'));
    }

    public function test_an_email_that_has_an_account_is_never_signed_in_by_the_form(): void
    {
        User::factory()->create(['email' => 'new@example.com']);

        $this->order()->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertSame(1, User::where('email', 'new@example.com')->count());
    }

    public function test_the_privacy_agreement_is_required(): void
    {
        $this->order(['privacy_consent' => ''])->assertSessionHasErrors('privacy_consent');

        $this->assertDatabaseMissing('users', ['email' => 'new@example.com']);
    }

    public function test_too_many_new_accounts_from_one_address_are_stopped(): void
    {
        config(['services.marketplace_guest_orders.per_ip_per_hour' => 1]);

        $this->order();
        auth()->logout();
        $this->order(['email' => 'second@example.com']);

        $this->assertDatabaseMissing('users', ['email' => 'second@example.com']);
    }

    public function test_after_paying_the_order_is_placed_once(): void
    {
        $buyer = User::factory()->create(['user_balance' => 100, 'currency_id' => 1]);
        $this->actingAs($buyer)->withSession(['marketplace.pending_package_id' => $this->package->id]);

        $this->get('/marketplace/orders/resume')->assertOk()->assertSee('resume-order', false);
        $this->post('/marketplace/orders', ['package_id' => $this->package->id])->assertRedirect();

        $this->assertSame(1, ServiceOrder::where('buyer_id', $buyer->id)->count());
        $this->get('/marketplace/orders/resume')->assertRedirect(route('marketplace.orders.index'));
    }

    public function test_while_the_payment_is_not_confirmed_the_order_waits(): void
    {
        $buyer = User::factory()->create(['user_balance' => 0, 'currency_id' => 1]);

        $this->actingAs($buyer)->withSession(['marketplace.pending_package_id' => $this->package->id])
            ->get('/marketplace/orders/resume')->assertOk()->assertDontSee('resume-order', false);
    }

    public function test_a_signed_in_buyer_without_enough_balance_goes_to_payment(): void
    {
        $buyer = User::factory()->create(['user_balance' => 0, 'currency_id' => 1]);

        $response = $this->actingAs($buyer)->post('/marketplace/orders', ['package_id' => $this->package->id]);

        $this->assertStringContainsString('kashier', strtolower($response->headers->get('Location')));
        $this->assertSame(0, ServiceOrder::where('buyer_id', $buyer->id)->count());
    }

    public function test_the_marketers_service_link_brings_the_visitor_to_the_service_and_ties_the_account(): void
    {
        $marketer = User::factory()->create();
        $this->referral($marketer);

        $this->get('/r/MARKET1?to=marketplace/services/'.$this->package->service_id)
            ->assertRedirect('/marketplace/services/'.$this->package->service_id);
        $this->order();

        $this->assertSame($marketer->id, User::where('email', 'new@example.com')->value('ref_user_id'));
    }

    public function test_the_service_page_gives_a_signed_in_marketer_a_link_for_this_service(): void
    {
        $marketer = User::factory()->create(['currency_id' => 1]);
        $this->referral($marketer);

        $this->actingAs($marketer)->get('/marketplace/services/'.$this->package->service_id.'/bulk-tool')->assertOk()
            ->assertSee('/r/MARKET1?to=marketplace/services/'.$this->package->service_id, false);
    }

    public function test_a_guest_sees_the_order_form_instead_of_a_sign_in_wall(): void
    {
        $this->get('/marketplace/services/'.$this->package->service_id.'/bulk-tool?lang=ar')->assertOk()
            ->assertSee('name="privacy_consent"', false)->assertSee('اطلب دلوقتي');
    }

    public function test_the_marketer_is_paid_once_from_the_sellers_share(): void
    {
        $marketer = User::factory()->create(['user_balance' => 0, 'currency_id' => 1]);
        $buyer = User::factory()->create(['ref_user_id' => $marketer->id, 'currency_id' => 1]);
        $escrow = $this->completedOrder($buyer);

        app(EscrowService::class)->releaseFunds($escrow);

        $this->assertEquals(10.0, $marketer->fresh()->user_balance);
        $this->assertEquals(80.0, $this->seller->fresh()->user_balance);

        $this->expectException(\Exception::class);
        app(EscrowService::class)->releaseFunds($escrow->fresh());
    }

    public function test_nobody_is_paid_when_the_buyer_was_not_referred(): void
    {
        $firstMarketer = User::factory()->create(['user_balance' => 0, 'currency_id' => 1]);
        $this->referral($firstMarketer);
        $escrow = $this->completedOrder(User::factory()->create(['currency_id' => 1]));

        app(EscrowService::class)->releaseFunds($escrow);

        $this->assertEquals(0.0, $firstMarketer->fresh()->user_balance);
        $this->assertEquals(90.0, $this->seller->fresh()->user_balance);
    }
}
