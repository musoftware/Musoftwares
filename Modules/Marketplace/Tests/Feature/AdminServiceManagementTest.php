<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\Conversation;
use App\Models\Currency;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Spatie\Permission\Models\Role;
use Tests\Feature\Concerns\SeedsUsdEgpRates;
use Tests\TestCase;

class AdminServiceManagementTest extends TestCase
{
    use RefreshDatabase;
    use SeedsUsdEgpRates;

    private User $admin;

    private User $seller;

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
        $this->currency = Currency::firstOrCreate(['currency' => 'USD', 'symbol' => '$'], ['exchange_rate' => 1.0]);
        $this->category = ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
    }

    private function makeUser(array $attributes = []): User
    {
        return User::factory()->create($attributes + ['email_verified_at' => now(), 'onboarding_completed' => true]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'seller_id' => $this->seller->id,
            'status' => 'active',
            'category_id' => $this->category->id,
            'title_translations' => ['en' => 'Logo design', 'ar' => 'تصميم لوجو'],
            'tagline_translations' => ['en' => 'A clean logo', 'ar' => 'لوجو نظيف'],
            'description_translations' => ['en' => 'A full logo design service.', 'ar' => ''],
            'auto_reply_translations' => ['en' => 'Thanks for your order', 'ar' => 'شكرا لطلبك'],
            'auto_complete' => false,
            'tags' => ['logo'],
            'requirements' => ['Your brand name'],
            'video_url' => 'https://example.com/video',
            'packages' => [[
                'name' => 'Basic', 'description' => 'One logo', 'price' => 100,
                'currency_id' => $this->currency->id, 'delivery_days' => 3, 'revisions' => 4,
            ]],
            'extras' => [['title' => 'Fast delivery', 'price' => 10, 'duration_days' => 1]],
            'faq' => [['question' => 'How long?', 'answer' => 'Three days.']],
        ], $overrides);
    }

    private function makeService(array $attributes = []): Service
    {
        $service = Service::create($attributes + [
            'seller_id' => $this->seller->id, 'title' => 'Logo design', 'description' => 'A full logo design service.',
            'category_id' => $this->category->id, 'status' => 'active',
        ]);
        ServicePackage::create([
            'service_id' => $service->id, 'name' => 'Basic', 'description' => 'One logo', 'price' => 100,
            'currency_id' => $this->currency->id, 'delivery_days' => 3, 'revisions' => 4, 'features' => ['One concept'],
        ]);

        return $service;
    }

    private function buy(Service $service, string $locale = 'ar'): ServiceOrder
    {
        app()->setLocale($locale);
        $buyer = $this->makeUser();
        $buyer->add_balance(1000, 'Top up', 'received', $this->currency->id);

        $this->actingAs($buyer)
            ->post(route('marketplace.orders.store'), ['package_id' => $service->packages()->first()->id])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return ServiceOrder::where('buyer_id', $buyer->id)->firstOrFail();
    }

    private function messagesOf(ServiceOrder $order)
    {
        $conversation = Conversation::where('conversable_type', ServiceOrder::class)->where('conversable_id', $order->id)->firstOrFail();

        return Message::where('conversation_id', $conversation->id)->get();
    }

    public function test_admin_creates_a_full_service_for_another_user(): void
    {
        $this->actingAs($this->admin)->post(route('admin.marketplace.services.store'), $this->payload())
            ->assertRedirect(route('admin.marketplace.services.all'))
            ->assertSessionHasNoErrors();

        $service = Service::with(['packages', 'extras'])->firstOrFail();
        $this->assertSame($this->seller->id, (int) $service->seller_id);
        $this->assertSame('Logo design', $service->title);
        $this->assertSame('شكرا لطلبك', $service->auto_reply_translations['ar']);
        $this->assertSame(['logo'], $service->tags);
        $this->assertSame(['Your brand name'], $service->requirements);
        $this->assertNotNull($service->approved_at);
        $this->assertCount(1, $service->packages);
        $this->assertSame(4, (int) $service->packages[0]->revisions);
        $this->assertCount(1, $service->extras);
    }

    public function test_admin_update_saves_every_field_changes_the_owner_and_keeps_package_ids(): void
    {
        $service = $this->makeService();
        $package = $service->packages()->first();
        $newOwner = $this->makeUser();

        $this->actingAs($this->admin)->put(route('admin.marketplace.services.update', $service), $this->payload([
            'seller_id' => $newOwner->id,
            'status' => 'suspended',
            'packages' => [[
                'id' => $package->id, 'name' => 'Basic 2', 'description' => 'Two logos', 'price' => 150,
                'currency_id' => $this->currency->id, 'delivery_days' => 5,
            ]],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $service->refresh();
        $package->refresh();
        $this->assertSame($newOwner->id, (int) $service->seller_id);
        $this->assertSame('suspended', $service->status);
        $this->assertSame('Thanks for your order', $service->auto_reply);
        $this->assertSame(1, $service->packages()->count());
        $this->assertSame('Basic 2', $package->name);
        $this->assertSame(4, (int) $package->revisions, 'a field the form did not send stays as it was');
        $this->assertSame(['One concept'], $package->features);
        $this->assertSame(1, $service->extras()->count());
    }

    public function test_removed_packages_are_soft_deleted_and_past_orders_stay_with_the_old_owner(): void
    {
        $service = $this->makeService();
        $order = $this->buy($service);
        $oldPackage = $service->packages()->first();
        $newOwner = $this->makeUser();

        $this->actingAs($this->admin)->put(route('admin.marketplace.services.update', $service), $this->payload(['seller_id' => $newOwner->id]))
            ->assertSessionHasNoErrors();

        $this->assertSoftDeleted($oldPackage);
        $this->assertSame($this->seller->id, (int) $order->fresh()->seller_id);
        $this->assertSame($newOwner->id, (int) $this->buy($service->fresh())->seller_id);
    }

    public function test_a_package_of_another_service_cannot_be_taken_over(): void
    {
        $other = $this->makeService();
        $otherPackage = $other->packages()->first();
        $service = $this->makeService();

        $this->actingAs($this->admin)->put(route('admin.marketplace.services.update', $service), $this->payload([
            'packages' => [[
                'id' => $otherPackage->id, 'name' => 'Stolen', 'description' => 'x', 'price' => 5,
                'currency_id' => $this->currency->id, 'delivery_days' => 1,
            ]],
        ]))->assertSessionHasNoErrors();

        $this->assertSame('Basic', $otherPackage->fresh()->name);
    }

    public function test_auto_complete_needs_an_auto_reply(): void
    {
        $this->actingAs($this->admin)->post(route('admin.marketplace.services.store'), $this->payload([
            'auto_complete' => true,
            'auto_reply_translations' => ['en' => '', 'ar' => ''],
        ]))->assertSessionHasErrors('auto_reply');

        $this->assertSame(0, Service::count());
    }

    public function test_non_admin_cannot_use_the_admin_form(): void
    {
        $this->actingAs($this->seller)->post(route('admin.marketplace.services.store'), $this->payload())->assertStatus(403);
        $this->assertSame(0, Service::count());
    }

    public function test_buyer_gets_the_auto_reply_in_their_language_and_the_order_stays_open(): void
    {
        $service = $this->makeService(['auto_reply' => 'Thanks', 'auto_reply_translations' => ['en' => 'Thanks', 'ar' => 'شكرا لطلبك']]);

        $order = $this->buy($service, 'ar');
        $messages = $this->messagesOf($order);

        $this->assertCount(1, $messages);
        $this->assertSame('شكرا لطلبك', $messages[0]->body);
        $this->assertSame($this->seller->id, (int) $messages[0]->sender_id);
        $this->assertSame(ServiceOrderStatus::PENDING, $order->status);
    }

    public function test_no_auto_reply_means_no_message(): void
    {
        $this->assertCount(0, $this->messagesOf($this->buy($this->makeService())));
    }

    public function test_auto_complete_delivers_at_once_and_the_command_completes_it_after_three_days(): void
    {
        $service = $this->makeService(['auto_reply' => 'Your download link', 'auto_complete' => true]);

        $order = $this->buy($service);
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->status);
        $this->assertSame('Your download link', $order->delivery_payload['message']);
        $this->assertNotNull($order->auto_complete_at);

        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::DELIVERED, $order->fresh()->status, 'not before the three days pass');

        $this->travel(4)->days();
        Artisan::call('marketplace:auto-complete-orders');
        $this->assertSame(ServiceOrderStatus::COMPLETED, $order->fresh()->status);
    }

    public function test_a_seller_edit_does_not_erase_the_auto_reply(): void
    {
        $service = $this->makeService(['auto_reply' => 'Keep me', 'auto_reply_translations' => ['en' => 'Keep me']]);

        $this->actingAs($this->seller)->put(route('marketplace.services.update', $service), [
            'title' => 'Logo design', 'description' => 'A full logo design service.', 'category_id' => $this->category->id,
            'packages' => [[
                'id' => $service->packages()->first()->id, 'name' => 'Basic', 'description' => 'One logo', 'price' => 100,
                'currency_id' => $this->currency->id, 'delivery_days' => 3,
            ]],
        ])->assertSessionHasNoErrors();

        $this->assertSame('Keep me', $service->fresh()->auto_reply);
    }
}
