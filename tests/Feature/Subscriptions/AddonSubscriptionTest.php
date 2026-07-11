<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class AddonSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup initial required data
        $currency = Currency::firstOrCreate(['currency' => 'USD'], ['rate' => 1]);
        $currencyEgp = Currency::firstOrCreate(['currency' => 'EGP'], ['rate' => 50]);

        $this->user = User::factory()->create([
            'user_balance' => 10000,
            'currency_id' => $currency->id,
        ]);

        $this->tenant = Tenant::create([
            'id' => 'test-tenant-'.uniqid(),
            'user_id' => $this->user->id,
            'name' => 'Test Tenant',
            'status' => 'active',
        ]);

        // The erp_tenants table belongs to user_id. We don't need to save tenant_id on user.

        // Ensure config is set for testing
        config(['saas.modules' => [
            'erp' => 1000,
        ]]);

        config(['saas.addons' => [
            'erp-hr-addon' => [
                'name' => 'HR Addon',
                'parent' => 'erp',
                'price' => 500,
                'desc' => 'HR',
                'icon' => 'Users',
            ],
        ]]);
    }

    public function test_new_user_can_subscribe_to_erp()
    {
        $response = $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year',
        ]);

        $response->assertRedirect(route('subscriptions.manage'));
        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp',
        ]);

        $this->user->refresh();
    }

    public function test_active_user_can_add_new_addon_as_prorated_upgrade()
    {
        // 1. Subscribe to ERP first
        $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year',
        ]);

        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp',
        ]);

        $initialBalance = $this->user->refresh()->user_balance;
        $initialPlanId = $this->user->plan_id;

        // 2. Add Addon
        $response = $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp', 'erp-hr-addon'],
            'billing_cycle' => '1_year',
        ]);

        $response->assertRedirect(route('subscriptions.manage'));

        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp',
        ]);

        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp-hr-addon',
        ]);

        $this->user->refresh();
        $this->assertLessThan($initialBalance, $this->user->user_balance);
    }

    public function test_subscription_fails_if_addon_purchased_without_parent_module()
    {
        $response = $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp-hr-addon'],
            'billing_cycle' => '1_year',
        ]);

        $response->assertSessionHasErrors(['error']);

        $this->assertDatabaseMissing('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp-hr-addon',
        ]);
    }
}
