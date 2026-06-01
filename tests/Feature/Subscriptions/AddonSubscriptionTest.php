<?php

namespace Tests\Feature\Subscriptions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use App\Models\Plan;
use Modules\ERP\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\Currency;

class AddonSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

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
            'id' => 'test-tenant-' . uniqid(),
            'user_id' => $this->user->id,
        ]);
        
        $this->user->tenant_id = $this->tenant->id;
        $this->user->save();
        
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
                'icon' => 'Users'
            ],
        ]]);
    }

    public function test_new_user_can_subscribe_to_erp()
    {
        $response = $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year'
        ]);

        $response->assertRedirect(route('subscriptions.manage'));
        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp',
        ]);
        
        $this->user->refresh();
        $this->assertNotNull($this->user->plan_id);
    }

    public function test_active_user_can_add_new_addon_as_prorated_upgrade()
    {
        // 1. Subscribe to ERP first
        $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year'
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
            'billing_cycle' => '1_year'
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
        $this->assertNotEquals($initialPlanId, $this->user->plan_id);
        $this->assertLessThan($initialBalance, $this->user->user_balance);
    }

    public function test_subscription_fails_if_addon_purchased_without_parent_module()
    {
        $response = $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp-hr-addon'],
            'billing_cycle' => '1_year'
        ]);

        $response->assertSessionHasErrors(['error']);
        
        $this->assertDatabaseMissing('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp-hr-addon',
        ]);
    }
    
    public function test_downgrade_removes_unselected_features()
    {
        // Subscribe to ERP + Addon
        $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp', 'erp-hr-addon'],
            'billing_cycle' => '1_year'
        ]);
        
        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp-hr-addon',
        ]);
        
        // Downgrade to just ERP
        $this->actingAs($this->user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp'],
            'billing_cycle' => '1_year'
        ]);
        
        $this->assertDatabaseMissing('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp-hr-addon',
        ]);
        
        $this->assertDatabaseHas('tenant_features', [
            'tenant_id' => $this->tenant->id,
            'feature_key' => 'erp',
        ]);
    }
}
