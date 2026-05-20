<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Freelance\Models\PointPackage;
use Modules\Freelance\Models\PointTransaction;
use Modules\Core\Models\Wallet;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Helpers\KashierHelper;

class PointPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected PointPackage $package;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->wallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $this->user->id,
            'context' => 'user',
            'balance' => 100.00,
            'currency' => 'USD',
        ]);

        $this->package = PointPackage::create([
            'name' => 'Starter Pack',
            'points' => 100,
            'price' => 10.00,
            'currency_code' => 'USD',
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

        // Check wallet balance deducted
        $this->assertEquals(90.00, $this->wallet->fresh()->balance);

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

        // Set wallet balance below package price
        $this->wallet->update(['balance' => 5.00]);

        $response = $this->post(route('freelance.point-purchases.store'), [
            'package_id' => $this->package->id,
        ]);

        // It should perform an Inertia external redirect
        $response->assertStatus(409); // Conflict status code for Inertia::location
        $response->assertHeader('X-Inertia-Location');
        
        $location = $response->headers->get('X-Inertia-Location');
        $this->assertStringContainsString('payments.kashier.io', $location);

        // Wallet balance must not change
        $this->assertEquals(5.00, $this->wallet->fresh()->balance);
    }

    public function test_purchase_custom_points_via_wallet_success(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('freelance.point-purchases.store-wallet'), [
            'points' => 50, // Cost should be 50 * 0.10 = $5.00
        ]);

        $response->assertStatus(302);
        $response->assertSessionHas('success');

        // Check wallet balance deducted
        $this->assertEquals(95.00, $this->wallet->fresh()->balance);

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

        // Set wallet balance below cost
        $this->wallet->update(['balance' => 2.00]);

        $response = $this->post(route('freelance.point-purchases.store-wallet'), [
            'points' => 50, // Cost is $5.00
        ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location');

        $location = $response->headers->get('X-Inertia-Location');
        $this->assertStringContainsString('payments.kashier.io', $location);

        // Wallet balance must not change
        $this->assertEquals(2.00, $this->wallet->fresh()->balance);
    }
}
