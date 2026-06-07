<?php

namespace Tests\Feature\Subscriptions;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Modules\ERP\Models\Tenant;
use App\Models\UserSubscription;
use App\Models\TenantFeature;
use App\Models\Currency;

class ToolSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure USD currency exists
        Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$']
        );
        Currency::firstOrCreate(
            ['currency' => 'EGP'],
            ['symbol' => 'EGP']
        );
    }

    // Legacy trial tests removed because subscriptions.trial route no longer exists

    public function test_tenant_is_created_successfully_without_tenant_id_column_error_during_wallet_subscribe()
    {
        $user = User::factory()->create([
            'user_balance' => 50000 // sufficient balance
        ]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp', 'tool-123'],
            'billing_cycle' => '1_year',
            'is_new_system' => true,
        ]);

        $response->assertSessionHas('success');

        // Verify tenant was created
        $tenant = Tenant::where('user_id', $user->id)->first();
        $this->assertNotNull($tenant);

        // Verify subscriptions were created
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'erp',
        ]);
        
        $this->assertDatabaseHas('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'tool-123',
        ]);
    }

    public function test_user_cannot_subscribe_to_tools_without_sufficient_balance()
    {
        $user = User::factory()->create([
            'user_balance' => 0 // 0 balance
        ]);

        // Add dummy tool price config
        config(['tools.123' => [
            'is_free' => false,
            'plans' => [
                ['price_monthly' => 100]
            ]
        ]]);

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['tool-123'],
            'billing_cycle' => '1_year',
            'is_new_system' => true,
        ]);

        $response->assertSessionHasErrors(['error' => 'Insufficient balance.']);

        $this->assertDatabaseMissing('user_subscriptions', [
            'user_id' => $user->id,
            'object' => 'tool-123',
        ]);
    }
}
