<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserSubscription;
use Carbon\Carbon;

class SubscriptionModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // create required currencies
        \App\Models\Currency::create(['currency' => 'EGP', 'exchange_rate' => 1]);
        \App\Models\Currency::create(['currency' => 'USD', 'exchange_rate' => 50]);
    }

    public function test_user_can_purchase_addon_if_they_own_parent_module()
    {
        $user = User::factory()->create([
            'user_balance' => 1000,
            'currency_id' => 1,
        ]);

        // User already owns ERP
        UserSubscription::create([
            'client_id' => $user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(365),
            'auto_renew' => true,
        ]);

        // Attempt to subscribe to just the addon
        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp-backup'], // only the addon
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHas('success', 'Subscribed to modules successfully!');

        // Check user_subscriptions has the addon
        $this->assertDatabaseHas('user_subscriptions', [
            'client_id' => $user->id,
            'object' => 'erp-backup',
            'status' => 'active',
        ]);

        // Check balance deduction (ERP Backup is $50/month EGP)
        // Since we test 1 month, amount is 50
        $user->refresh();
        $this->assertEquals(950, $user->user_balance);
    }

    public function test_user_cannot_purchase_addon_without_parent_module()
    {
        $user = User::factory()->create([
            'user_balance' => 1000,
            'currency_id' => 1,
        ]);

        // User does NOT own ERP

        $response = $this->actingAs($user)->post(route('subscriptions.subscribe'), [
            'items' => ['erp-backup'], // only the addon
            'billing_cycle' => '1_month',
            'is_new_system' => false,
        ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertStringContainsString('cannot subscribe', session('errors')->first('error'));
    }
}
