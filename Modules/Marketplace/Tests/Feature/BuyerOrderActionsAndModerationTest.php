<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\Conversation;
use App\Models\Currency;
use App\Models\Message;
use App\Models\User;
use App\Notifications\ServiceModerationNotification;
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

class BuyerOrderActionsAndModerationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private User $admin;

    private User $seller;

    private User $buyer;

    private Currency $currency;

    private ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedUsdEgpRates();
        Role::firstOrCreate(['name' => 'admin']);

        $this->admin = $this->makeUser();
        $this->admin->assignRole('admin');
        $this->seller = $this->makeUser();
        $this->buyer = $this->makeUser();
        $this->currency = Currency::firstOrCreate(['currency' => 'USD', 'symbol' => '$'], ['exchange_rate' => 1.0]);
        $this->category = ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $this->buyer->add_balance(1000, 'Top up', 'received', $this->currency->id);
    }

    private function makeUser(): User
    {
        return User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
    }

    private function makeService(array $attributes = []): Service
    {
        $service = Service::create($attributes + [
            'seller_id' => $this->seller->id, 'title' => 'Logo design', 'description' => 'A full logo design service.',
            'category_id' => $this->category->id, 'status' => 'active', 'auto_reply' => 'Your link', 'auto_complete' => true,
        ]);
        ServicePackage::create([
            'service_id' => $service->id, 'name' => 'Basic', 'description' => 'One logo', 'price' => 100,
            'currency_id' => $this->currency->id, 'delivery_days' => 3,
        ]);

        return $service;
    }

    private function buyDelivered(): ServiceOrder
    {
        $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), ['package_id' => $this->makeService()->packages()->first()->id])
            ->assertSessionHasNoErrors();

        return ServiceOrder::where('buyer_id', $this->buyer->id)->latest('id')->firstOrFail();
    }

    private function chatBodies(ServiceOrder $order): array
    {
        $conversation = Conversation::where('conversable_type', ServiceOrder::class)->where('conversable_id', $order->id)->firstOrFail();

        return Message::where('conversation_id', $conversation->id)->pluck('body')->all();
    }

    public function test_order_page_has_the_revision_note_and_cancel_fields(): void
    {
        $order = $this->buyDelivered();

        $this->actingAs($this->buyer)->get(route('marketplace.orders.show', $order))
            ->assertOk()
            ->assertSee('name="revision_note"', false)
            ->assertSee('name="reason"', false);
    }

    public function test_both_sides_see_the_order_conversation_and_can_reply_safely(): void
    {
        $order = $this->buyDelivered();

        $this->actingAs($this->buyer)->post(route('marketplace.orders.messages.store', $order), ['body' => '<b>Thanks</b>'])->assertRedirect();

        foreach ([$this->buyer, $this->seller] as $user) {
            $this->actingAs($user)->get(route('marketplace.orders.show', $order))
                ->assertOk()
                ->assertSee('Your link')
                ->assertSee('name="body"', false)
                ->assertSee('&lt;b&gt;Thanks&lt;/b&gt;', false)
                ->assertDontSee('<b>Thanks</b>', false);
        }

        $this->actingAs($this->makeUser())->get(route('marketplace.orders.show', $order))->assertForbidden();
    }

    public function test_a_revision_request_stops_auto_complete_until_the_seller_delivers_again(): void
    {
        $order = $this->buyDelivered();

        $this->actingAs($this->buyer)->post(route('marketplace.orders.revision', $order), ['revision_note' => 'Change the color'])
            ->assertSessionHasNoErrors();

        $this->assertSame(ServiceOrderStatus::REVISION, $order->fresh()->status);
        $this->assertContains('Change the color', $this->chatBodies($order));

        $this->travel(4)->days();
        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::REVISION, $order->fresh()->status);

        $this->actingAs($this->seller)->post(route('marketplace.orders.deliver', $order), ['message' => 'Fixed'])->assertSessionHasNoErrors();
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->fresh()->status);

        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->fresh()->status, 'the three days start again');

        $this->travel(4)->days();
        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::COMPLETED, $order->fresh()->status);
    }

    public function test_rejection_saves_the_reason_and_tells_the_seller(): void
    {
        Notification::fake();
        $service = $this->makeService(['status' => 'draft']);

        $this->actingAs($this->admin)->post(route('admin.marketplace.services.reject', $service), ['note' => 'Bad images'])->assertRedirect();

        $service->refresh();
        $this->assertSame('rejected', $service->status);
        $this->assertSame('Bad images', $service->rejection_reason);
        $this->assertNotNull($service->rejected_at);
        Notification::assertSentTo($this->seller, ServiceModerationNotification::class, fn ($sent) => $sent->decision === 'rejected' && $sent->reason === 'Bad images');
    }

    public function test_approval_records_the_admin_and_clears_an_old_rejection(): void
    {
        Notification::fake();
        $service = $this->makeService(['status' => 'rejected', 'rejection_reason' => 'Old', 'rejected_at' => now()]);

        $this->actingAs($this->admin)->post(route('admin.marketplace.services.approve', $service))->assertRedirect();

        $service->refresh();
        $this->assertSame('active', $service->status);
        $this->assertSame($this->admin->id, (int) $service->approved_by);
        $this->assertNull($service->rejection_reason);
        Notification::assertSentTo($this->seller, ServiceModerationNotification::class, fn ($sent) => $sent->decision === 'approved');
    }

    public function test_the_notice_renders_as_a_real_mail(): void
    {
        $service = $this->makeService();

        $mail = (new ServiceModerationNotification($service, 'rejected', 'Bad images'))->toMail($this->seller);

        $this->assertStringContainsString('Bad images', implode(' ', $mail->introLines));
        $this->assertStringContainsString((string) $service->id, $mail->actionUrl);
    }

    public function test_bulk_approve_and_reject(): void
    {
        Notification::fake();
        $first = $this->makeService(['status' => 'draft']);
        $second = $this->makeService(['status' => 'draft']);

        $url = route('admin.marketplace.services.bulk');
        $this->actingAs($this->admin)->post($url, ['ids' => [$first->id], 'action' => 'reject'])->assertSessionHasErrors('note');
        $this->actingAs($this->seller)->post($url, ['ids' => [$first->id], 'action' => 'approve'])->assertForbidden();
        $this->assertSame('draft', $first->fresh()->status);

        $this->actingAs($this->admin)->post($url, ['ids' => [$first->id, $second->id], 'action' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame('active', $first->fresh()->status);
        $this->assertSame('active', $second->fresh()->status);

        $this->actingAs($this->admin)->post($url, ['ids' => [$first->id], 'action' => 'reject', 'note' => 'Bad'])->assertSessionHasNoErrors();
        $this->assertSame('Bad', $first->fresh()->rejection_reason);
    }

    public function test_seller_can_write_and_clear_the_auto_reply_and_requirements_as_text(): void
    {
        $service = $this->makeService(['auto_reply_translations' => ['ar' => 'قديم']]);
        $payload = fn (array $extra) => $extra + [
            'title' => 'Logo design', 'description' => 'A full logo design service.', 'category_id' => $this->category->id,
            'packages' => [[
                'id' => $service->packages()->first()->id, 'name' => 'Basic', 'description' => 'One logo', 'price' => 100,
                'currency_id' => $this->currency->id, 'delivery_days' => 3,
            ]],
        ];

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), $payload([
            'auto_reply' => 'New text', 'requirements' => "Your name\nYour logo",
        ]))->assertSessionHasNoErrors();

        $service->refresh();
        $this->assertSame('New text', $service->auto_reply);
        $this->assertNull($service->auto_reply_translations, 'older translated copies must not hide the new text');
        $this->assertSame(['Your name', 'Your logo'], $service->requirements);

        $this->actingAs($this->seller)->get(route('marketplace.services.edit', $service))
            ->assertOk()->assertSee('name="auto_reply"', false)->assertSee('Your name');

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), $payload(['auto_reply' => '']))->assertSessionHasNoErrors();
        $this->assertNull($service->fresh()->auto_reply);
    }

    public function test_seller_create_page_has_the_auto_reply_field(): void
    {
        $this->actingAs($this->seller)->get(route('marketplace.services.create'))->assertOk()->assertSee('name="auto_reply"', false);
    }

    public function test_api_order_holds_the_money_and_sends_the_auto_reply(): void
    {
        $service = $this->makeService();
        $package = $service->packages()->first();

        $response = $this->actingAs($this->buyer)->postJson(route('marketplace.api.v1.orders.create'), [
            'service_id' => $service->id, 'package_id' => $package->id,
        ])->assertCreated();

        $order = ServiceOrder::findOrFail($response->json('order.id'));
        $this->assertSame($this->seller->id, (int) $order->seller_id);
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->status);
        $this->assertSame(EscrowStatus::HELD, MarketplaceEscrow::where('order_id', $order->id)->first()->status);
        $this->assertSame(['Your link'], $this->chatBodies($order));
    }

    public function test_api_refuses_a_buyer_without_enough_balance(): void
    {
        $service = $this->makeService();
        $poorBuyer = $this->makeUser();

        $this->actingAs($poorBuyer)->postJson(route('marketplace.api.v1.orders.create'), [
            'service_id' => $service->id, 'package_id' => $service->packages()->first()->id,
        ])->assertStatus(422);

        $this->assertSame(0, ServiceOrder::where('buyer_id', $poorBuyer->id)->count());
    }
}
