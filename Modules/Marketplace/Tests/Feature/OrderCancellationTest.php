<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use App\Notifications\OrderCancellationRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Modules\Marketplace\Enums\EscrowStatus;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class OrderCancellationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private User $admin;

    private User $seller;

    private User $buyer;

    private ServiceOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        Role::firstOrCreate(['name' => 'admin']);

        $this->admin = $this->makeUser();
        $this->admin->assignRole('admin');
        $this->seller = $this->makeUser();
        $this->buyer = $this->makeUser();

        $currency = Currency::firstOrCreate(['currency' => 'USD', 'symbol' => '$'], ['exchange_rate' => 1.0]);
        $this->buyer->add_balance(1000, 'Top up', 'received', $currency->id);

        $service = Service::create([
            'seller_id' => $this->seller->id, 'title' => 'Logo design', 'description' => 'A full logo design service.',
            'category_id' => ServiceCategory::create(['name' => 'Test', 'slug' => 'test'])->id, 'status' => 'active',
            'auto_reply' => 'Your link', 'auto_complete' => true,
        ]);
        $package = ServicePackage::create([
            'service_id' => $service->id, 'name' => 'Basic', 'description' => 'One logo', 'price' => 100,
            'currency_id' => $currency->id, 'delivery_days' => 3,
        ]);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id])->assertSessionHasNoErrors();
        $this->order = ServiceOrder::where('buyer_id', $this->buyer->id)->firstOrFail();
    }

    private function makeUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    }

    private function ask(User $user, string $reason = 'Wrong service')
    {
        return $this->actingAs($user)->post(route('marketplace.orders.cancel-request', $this->order), ['reason' => $reason]);
    }

    private function escrowStatus(): EscrowStatus
    {
        return MarketplaceEscrow::where('order_id', $this->order->id)->first()->status;
    }

    public function test_a_request_tells_the_other_side_not_the_admin_and_stops_auto_complete(): void
    {
        Notification::fake();

        $this->ask($this->buyer)->assertSessionHasNoErrors();

        $this->assertSame(ServiceOrderStatus::CANCELLATION_REQUESTED, $this->order->fresh()->status);
        $this->assertSame(EscrowStatus::HELD, $this->escrowStatus(), 'this is not a dispute');
        Notification::assertSentTo($this->seller, OrderCancellationRequestedNotification::class);
        Notification::assertNotSentTo($this->admin, OrderCancellationRequestedNotification::class);

        $this->travel(2)->days();
        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::CANCELLATION_REQUESTED, $this->order->fresh()->status);
    }

    public function test_seller_accepting_cancels_and_refunds_the_buyer(): void
    {
        $this->ask($this->buyer);
        $before = $this->buyer->fresh()->available_balance();

        $this->actingAs($this->seller)->post(route('marketplace.orders.cancel-accept', $this->order))->assertSessionHasNoErrors();

        $this->assertSame(ServiceOrderStatus::CANCELLED, $this->order->fresh()->status);
        $this->assertSame(EscrowStatus::REFUNDED, $this->escrowStatus());
        $this->assertGreaterThan($before, $this->buyer->fresh()->available_balance());
    }

    public function test_seller_declining_restores_the_order_with_a_fresh_review_window(): void
    {
        $this->ask($this->buyer);
        $this->travel(2)->days();

        $this->actingAs($this->seller)->post(route('marketplace.orders.cancel-decline', $this->order))->assertSessionHasNoErrors();

        $order = $this->order->fresh();
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->status);
        $this->assertSame(EscrowStatus::HELD, $this->escrowStatus());

        $this->travel(2)->days();
        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->fresh()->status, 'the buyer gets a new three days');

        $this->travel(2)->days();
        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::COMPLETED, $order->fresh()->status);
    }

    public function test_the_seller_can_ask_too_and_the_buyer_answers(): void
    {
        $this->ask($this->seller, 'I cannot do this work')->assertSessionHasNoErrors();

        $this->actingAs($this->seller)->post(route('marketplace.orders.cancel-accept', $this->order))->assertForbidden();
        $this->actingAs($this->buyer)->post(route('marketplace.orders.cancel-accept', $this->order))->assertSessionHasNoErrors();

        $this->assertSame(ServiceOrderStatus::CANCELLED, $this->order->fresh()->status);
    }

    public function test_the_one_who_asked_and_strangers_cannot_answer(): void
    {
        $this->ask($this->buyer);

        foreach (['cancel-accept', 'cancel-decline'] as $route) {
            $this->actingAs($this->buyer)->post(route("marketplace.orders.{$route}", $this->order))->assertForbidden();
            $this->actingAs($this->makeUser())->post(route("marketplace.orders.{$route}", $this->order))->assertForbidden();
        }

        $this->assertSame(ServiceOrderStatus::CANCELLATION_REQUESTED, $this->order->fresh()->status);
    }

    public function test_strangers_cannot_ask_and_closed_orders_cannot_be_cancelled(): void
    {
        $this->ask($this->makeUser())->assertForbidden();
        $this->actingAs($this->buyer)->post(route('marketplace.orders.cancel-request', $this->order), [])->assertSessionHasErrors('reason');

        $this->order->update(['status' => ServiceOrderStatus::COMPLETED]);
        $this->ask($this->buyer)->assertSessionHasErrors('error');
        $this->assertSame(ServiceOrderStatus::COMPLETED, $this->order->fresh()->status);
    }

    public function test_no_answer_in_three_days_cancels_and_refunds(): void
    {
        $this->ask($this->buyer);

        $this->travel(2)->days();
        Artisan::call('marketplace:accept-expired-cancellations');
        $this->assertSame(ServiceOrderStatus::CANCELLATION_REQUESTED, $this->order->fresh()->status);

        $this->travel(2)->days();
        Artisan::call('marketplace:accept-expired-cancellations');
        $this->assertSame(ServiceOrderStatus::CANCELLED, $this->order->fresh()->status);
        $this->assertSame(EscrowStatus::REFUNDED, $this->escrowStatus());
    }

    public function test_answering_twice_refunds_once(): void
    {
        $this->ask($this->buyer);
        $this->actingAs($this->seller)->post(route('marketplace.orders.cancel-accept', $this->order));
        $after = $this->buyer->fresh()->available_balance();

        $this->actingAs($this->seller)->post(route('marketplace.orders.cancel-accept', $this->order))->assertForbidden();

        $this->assertSame($after, $this->buyer->fresh()->available_balance());
    }

    public function test_the_order_page_shows_each_side_the_right_controls(): void
    {
        $page = route('marketplace.orders.show', $this->order);
        $this->actingAs($this->seller)->get($page)->assertSee('name="reason"', false);

        $this->ask($this->buyer);

        $this->actingAs($this->seller)->get($page)
            ->assertSee(route('marketplace.orders.cancel-accept', $this->order), false)
            ->assertSee(route('marketplace.orders.cancel-decline', $this->order), false)
            ->assertSee('Wrong service');

        $this->actingAs($this->buyer)->get($page)
            ->assertDontSee(route('marketplace.orders.cancel-accept', $this->order), false)
            ->assertDontSee('name="reason"', false);
    }

    public function test_a_dispute_during_a_request_is_refused_without_a_crash(): void
    {
        $this->ask($this->buyer);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.dispute', $this->order))->assertRedirect()->assertSessionHasErrors('error');
        $this->assertSame(ServiceOrderStatus::CANCELLATION_REQUESTED, $this->order->fresh()->status);
    }

    public function test_admin_can_still_step_in_and_find_the_order(): void
    {
        $this->ask($this->buyer);

        $this->actingAs($this->admin)
            ->get(route('admin.marketplace.orders.index', ['status' => 'cancellation_requested']))
            ->assertInertia(fn ($page) => $page->has('orders.data', 1)->where('orders.data.0.id', $this->order->id));

        $this->actingAs($this->admin)->post(route('admin.marketplace.orders.dispute.resolve', $this->order->id), [
            'action' => 'refund_buyer', 'resolution_reason' => 'Agreed',
        ])->assertSessionHasNoErrors();

        $this->assertSame(ServiceOrderStatus::CANCELLED, $this->order->fresh()->status);
        $this->assertSame(EscrowStatus::REFUNDED, $this->escrowStatus());
    }
}
