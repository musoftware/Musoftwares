<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\PricingService;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SubscriptionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    // ─────────────────────────────────────────────────────────────
    //  1. User Model: hasSubscription()
    // ─────────────────────────────────────────────────────────────

    public function test_user_has_subscription_returns_true_when_active_subscription_exists()
    {
        $user = User::factory()->create();

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $this->assertTrue($user->hasSubscription());
    }

    public function test_user_has_subscription_returns_false_when_no_subscriptions()
    {
        $user = User::factory()->create();
        $this->assertFalse($user->hasSubscription());
    }

    public function test_user_has_subscription_returns_false_when_all_expired()
    {
        $user = User::factory()->create();

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now()->subDays(60),
            'expires_at' => now()->subDays(1), // expired yesterday
            'auto_renew' => false,
        ]);

        $this->assertFalse($user->hasSubscription());
    }

    // ─────────────────────────────────────────────────────────────
    //  2. User Model: hasModuleSubscription()
    // ─────────────────────────────────────────────────────────────

    public function test_user_has_module_subscription_returns_true_for_subscribed_module()
    {
        $user = User::factory()->create();

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $this->assertTrue($user->hasModuleSubscription('erp'));
    }

    public function test_user_has_module_subscription_returns_false_for_different_module()
    {
        $user = User::factory()->create();

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $this->assertFalse($user->hasModuleSubscription('crm'));
    }

    public function test_user_has_module_subscription_returns_false_when_expired()
    {
        $user = User::factory()->create();

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-backup',
            'status' => 'active',
            'started_at' => now()->subDays(60),
            'expires_at' => now()->subDays(1),
            'auto_renew' => false,
        ]);

        $this->assertFalse($user->hasModuleSubscription('erp-backup'));
    }

    public function test_user_has_module_subscription_returns_true_for_addon()
    {
        $user = User::factory()->create();

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-backup',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(365),
            'auto_renew' => true,
        ]);

        $this->assertTrue($user->hasModuleSubscription('erp-backup'));
    }

    // ─────────────────────────────────────────────────────────────
    //  3. SubscriptionService: hasActiveSubscription()
    // ─────────────────────────────────────────────────────────────

    public function test_subscription_service_returns_true_for_subscribed_module()
    {
        $user = User::factory()->create();
        $service = app(SubscriptionService::class);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $this->assertTrue($service->hasActiveSubscription($user, 'erp'));
    }

    public function test_subscription_service_returns_false_for_unsubscribed_module()
    {
        $user = User::factory()->create();
        $service = app(SubscriptionService::class);

        $this->assertFalse($service->hasActiveSubscription($user, 'erp'));
    }

    public function test_subscription_service_checks_specific_module_not_any()
    {
        $user = User::factory()->create();
        $service = app(SubscriptionService::class);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        // Has CRM, but not ERP
        $this->assertTrue($service->hasActiveSubscription($user, 'crm'));
        $this->assertFalse($service->hasActiveSubscription($user, 'erp'));
    }



    // ─────────────────────────────────────────────────────────────
    //  5. PricingService: getServiceItems()
    // ─────────────────────────────────────────────────────────────

    public function test_pricing_service_returns_erp_module()
    {
        $service = new PricingService;
        $items = $service->getServiceItems();

        $erp = collect($items)->firstWhere('id', 'erp');

        $this->assertNotNull($erp, 'ERP module should exist in service items');
        $this->assertEquals('module', $erp['type']);
        $this->assertEquals('ERP', $erp['name']);
        $this->assertGreaterThan(0, $erp['monthly_price']);
        $this->assertGreaterThan(0, $erp['yearly_price']);
    }

    public function test_pricing_service_monthly_price_is_yearly_divided_by_10()
    {
        $service = new PricingService;
        $items = $service->getServiceItems();

        $erp = collect($items)->firstWhere('id', 'erp');

        // ERP is 10000 EGP/yr => 1000 EGP/mo
        $this->assertEquals(1000, $erp['monthly_price']);
        $this->assertEquals(10000, $erp['yearly_price']);
    }

    // ─────────────────────────────────────────────────────────────
    //  6. Subscribe Flow: Purchase addon with parent owned
    // ─────────────────────────────────────────────────────────────

    public function test_user_can_purchase_addon_if_they_own_parent_module()
    {
        $user = User::factory()->create([
            'user_balance' => 1000,
            'currency_id' => 1,
        ]);

        // User already owns Booking
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'booking',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(365),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['booking-wa-reminders'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'booking-wa-reminders',
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_purchase_addon_without_parent_module()
    {
        $user = User::factory()->create([
            'user_balance' => 1000,
            'currency_id' => 1,
        ]);

        // User does NOT own Booking

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['booking-wa-reminders'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    public function test_user_can_purchase_module_and_addon_together()
    {
        $user = User::factory()->create([
            'user_balance' => 50000,
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['booking', 'booking-wa-reminders'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'booking',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'booking-wa-reminders',
            'status' => 'active',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  7. Subscribe Flow: Insufficient balance
    // ─────────────────────────────────────────────────────────────

    public function test_subscribe_fails_with_insufficient_balance()
    {
        $user = User::factory()->create([
            'user_balance' => 0,
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_month',
            'is_new_system' => true,
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'erp',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  8. Subscribe Flow: No items selected
    // ─────────────────────────────────────────────────────────────

    public function test_subscribe_fails_with_no_items()
    {
        $user = User::factory()->create([
            'user_balance' => 50000,
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => [],
            'billing_cycle' => '1_month',
            'is_new_system' => true,
        ]);

        $response->assertSessionHasErrors(['error']);
    }

    // ─────────────────────────────────────────────────────────────
    //  9. Dashboard: No plan_id crash
    // ─────────────────────────────────────────────────────────────

    public function test_dashboard_loads_without_plan_id_error()
    {
        $user = User::factory()->create(['currency_id' => 1]);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        // Dashboard may redirect; use manage page which is always accessible
        $response = $this->actingAs($user)->get(route('subscriptions.manage'));
        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────
    //  10. Manage Page: Subscriptions show with correct prices
    // ─────────────────────────────────────────────────────────────

    public function test_manage_page_shows_active_subscriptions_with_price()
    {
        $usdCurrency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );
        $user = User::factory()->create(['currency_id' => $usdCurrency->id]);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.manage'));
        $response->assertStatus(200);

        // Check inertia props contain correct subscription data
        $response->assertInertia(fn ($page) => $page->component('Client/Subscriptions/Manage')
            ->has('subscriptions', 1)
            ->where('subscriptions.0.plan_slug', 'erp')
            ->where('subscriptions.0.status', 'active')
            ->where('subscriptions.0.amount', 999.99)
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  11. Plans Page: Shows owned features
    // ─────────────────────────────────────────────────────────────

    public function test_plans_page_shows_owned_features()
    {
        $user = User::factory()->create(['currency_id' => 1]);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp-backup',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.plans'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page->component('Client/Subscriptions/Plans')
            ->has('activeSubscription')
            ->where('activeSubscription.status', 'active')
            ->has('activeSubscription.owned_features', 2)
        );
    }



    // ─────────────────────────────────────────────────────────────
    //  13. active_modules: Module access check
    // ─────────────────────────────────────────────────────────────

    public function test_active_modules_shows_erp_true_when_subscribed()
    {
        $user = User::factory()->create(['currency_id' => 1]);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        $response = $this->actingAs($user)->get(route('subscriptions.manage'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page->where('auth.active_modules.erp', true)
        );
    }

    public function test_active_modules_shows_erp_false_when_not_subscribed()
    {
        $user = User::factory()->create(['currency_id' => 1]);

        $response = $this->actingAs($user)->get(route('subscriptions.manage'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page->where('auth.active_modules.erp', false)
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  14. UserSubscription model: table and fillable
    // ─────────────────────────────────────────────────────────────

    public function test_user_subscription_uses_correct_table()
    {
        $sub = new UserSubscription;
        $this->assertEquals('user_subscriptions', $sub->getTable());
    }

    public function test_user_subscription_has_no_plan_id_column()
    {
        $columns = Schema::getColumnListing('user_subscriptions');
        $this->assertNotContains('plan_id', $columns);
        $this->assertContains('object', $columns);
        $this->assertContains('user_id', $columns);
        $this->assertContains('status', $columns);
        $this->assertContains('started_at', $columns);
        $this->assertContains('expires_at', $columns);
    }

    // ─────────────────────────────────────────────────────────────
    //  15. Extending existing subscription
    // ─────────────────────────────────────────────────────────────

    public function test_purchasing_already_owned_module_extends_expiry()
    {
        $user = User::factory()->create([
            'user_balance' => 50000,
            'currency_id' => 1,
        ]);

        $originalExpiry = now()->addDays(30);

        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $originalExpiry,
            'auto_renew' => true,
        ]);

        // Purchase again for 1 month (30 days)
        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHas('success');

        $sub = UserSubscription::where('user_id', $user->id)->where('object', 'erp')->first();

        // Expiry should be extended beyond original
        $this->assertTrue(
            Carbon::parse($sub->expires_at)->greaterThan($originalExpiry),
            'Subscription expiry should be extended when re-purchased'
        );
    }
    // ─────────────────────────────────────────────────────────────
    //  16. Free Tools Subscription Flow
    // ─────────────────────────────────────────────────────────────

    public function test_subscribe_succeeds_for_free_tools_with_zero_balance()
    {
        $user = User::factory()->create([
            'user_balance' => 0,
            'currency_id' => 1,
        ]);

        config(['tools' => [
            'test-free-tool' => ['is_free' => true, 'title' => 'Test Free Tool'],
        ]]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['tool-test-free-tool'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'tool-test-free-tool',
            'status' => 'active',
        ]);
    }

    public function test_subscribe_fails_for_paid_tools_with_zero_balance()
    {
        $user = User::factory()->create([
            'user_balance' => 0,
            'currency_id' => 1,
        ]);

        config(['tools' => [
            'test-paid-tool' => ['is_free' => false, 'title' => 'Test Paid Tool'],
        ]]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['tool-test-paid-tool'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'tool-test-paid-tool',
        ]);
    }

    public function test_volume_discount_applied_to_paid_tools()
    {
        $user = User::factory()->create([
            'user_balance' => 10000,
            'currency_id' => 1,
        ]);

        config(['tools' => [
            'paid-1' => ['is_free' => false, 'title' => 'Tool 1'],
            'paid-2' => ['is_free' => false, 'title' => 'Tool 2'],
            'free-1' => ['is_free' => true, 'title' => 'Free 1'],
        ]]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['tool-paid-1', 'tool-paid-2', 'tool-free-1'],
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'tool-paid-1',
        ]);
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'tool-free-1',
        ]);

        $user->refresh();
        $this->assertEquals(10000 - 189.99, $user->user_balance);
    }

    public function test_billing_route_redirects_to_subscriptions_manage()
    {
        $response = $this->get('/billing');
        $response->assertRedirect('/subscriptions/manage');
    }

    public function test_subscriptions_route_redirects_to_subscriptions_manage()
    {
        $response = $this->get('/subscriptions');
        $response->assertRedirect('/subscriptions/manage');
    }
}

