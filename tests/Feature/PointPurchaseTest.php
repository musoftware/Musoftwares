<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\PointPackage;
use App\Models\PointTransaction;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Helpers\KashierHelper;

class PointPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PointPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->user->update(['user_balance' => 100.00]);

        // Seed EGP to USD exchange rate (1 EGP = 0.02 USD)
        // Also need to create Currency model for EGP and attach to user if user_balance expects it.
        $egpCurrency = \App\Models\Currency::create(['currency' => 'EGP', 'symbol' => 'E£']);
        $usdCurrency = \App\Models\Currency::create(['currency' => 'USD', 'symbol' => '$']);
        $this->user->update(['currency' => $usdCurrency->id, 'preferred_currency' => 'USD']);

        \App\Models\CurrenciesExchange::create([
            'from_currency' => $egpCurrency->id,
            'to_currency' => $usdCurrency->id,
            'rate' => 0.02,
            'effective_date' => '2020-01-01',
            'source' => 'manual',
        ]);

        \App\Models\CurrenciesExchange::create([
            'from_currency' => 'EGP',
            'to_currency' => 'USD',
            'rate' => 0.02,
            'effective_date' => '2020-01-01',
            'source' => 'manual',
        ]);

        // Points package: Starter Pack (100 points, 100 EGP price)
        $this->package = PointPackage::create([
            'name' => 'Starter Pack',
            'points' => 100,
            'price' => 100.00,
            'currency_code' => 'EGP',
        ]);
    }

    public function test_purchase_package_using_wallet_success(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('freelance.point-purchases.store'), [
            'package_id' => $this->package->id,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Check wallet balance deducted: 100.00 - (100.00 EGP * 0.02) = 98.00 USD
        $this->assertEquals(98.00, $this->user->fresh()->user_balance);

        // Check Point Transaction created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->user->id,
            'points' => 100,
            'type' => 'purchased',
        ]);

        // Verify points balance is updated
        $this->assertEquals(100, $this->user->fresh()->points_balance);
    }

    public function test_purchase_package_insufficient_wallet_redirects_to_kashier(): void
    {
        $this->actingAs($this->user);

        // Set wallet balance below package price ($2.00 USD cost)
        $this->user->update(['user_balance' => 1.50]);

        $response = $this->post(route('freelance.point-purchases.store'), [
            'package_id' => $this->package->id,
        ], ['X-Inertia' => 'true']);

        // It should perform an Inertia external redirect
        $response->assertStatus(409); // Conflict status code for Inertia::location
        $response->assertHeader('X-Inertia-Location');
        
        $location = $response->headers->get('X-Inertia-Location');
        $this->assertStringContainsString('payments.kashier.io', $location);

        // Wallet balance must not change
        $this->assertEquals(1.50, $this->user->fresh()->user_balance);
    }

    public function test_purchase_custom_points_via_wallet_success(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('freelance.point-purchases.store-wallet'), [
            'points' => 50, // Cost should be 50 EGP * 0.02 = $1.00 USD
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Check wallet balance deducted: 100.00 - 1.00 = 99.00 USD
        $this->assertEquals(99.00, $this->user->fresh()->user_balance);

        // Check Point Transaction created
        $this->assertDatabaseHas('point_transactions', [
            'user_id' => $this->user->id,
            'points' => 50,
            'type' => 'purchased',
        ]);

        // Verify points balance is updated
        $this->assertEquals(50, $this->user->fresh()->points_balance);
    }

    public function test_purchase_custom_points_insufficient_wallet_redirects_to_kashier(): void
    {
        $this->actingAs($this->user);

        // Set wallet balance below cost ($1.00 USD)
        $this->user->update(['user_balance' => 0.50]);

        $response = $this->post(route('freelance.point-purchases.store-wallet'), [
            'points' => 50, // Cost is $1.00 USD
        ], ['X-Inertia' => 'true']);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location');

        $location = $response->headers->get('X-Inertia-Location');
        $this->assertStringContainsString('payments.kashier.io', $location);

        // Wallet balance must not change
        $this->assertEquals(0.50, $this->user->fresh()->user_balance);
    }
}
