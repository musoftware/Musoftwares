<?php

namespace Tests\Feature;

use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\PointPackage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected PointPackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $egpCurrency = Currency::firstOrCreate(
            ['currency' => 'EGP'],
            ['symbol' => 'e£', 'string_format' => 'e£%01.2f']
        );
        $usdCurrency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        $this->user->currency_id = $usdCurrency->id;
        $this->user->save();

        CurrenciesExchange::flushCache();

        CurrenciesExchange::updateOrCreate([
            'currency1' => $egpCurrency->id,
            'currency2' => $usdCurrency->id,
            'date_string' => now('Africa/Cairo')->toDateString(),
        ], [
            'rate' => 0.02,
        ]);

        CurrenciesExchange::updateOrCreate([
            'currency1' => $usdCurrency->id,
            'currency2' => $egpCurrency->id,
            'date_string' => now('Africa/Cairo')->toDateString(),
        ], [
            'rate' => 50.00,
        ]);

        CurrenciesExchange::flushCache();

        $this->user->add_balance(100.00, 'Initial Deposit', 'received', $usdCurrency->id);

        // Points package: Starter Pack (100 points, 100 EGP price)
        $this->package = PointPackage::create([
            'name' => 'Starter Pack',
            'points' => 100,
            'price' => 100.00,
        ]);
    }

    public function test_purchase_package_using_wallet_success(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('point-purchases.store'), [
            'package_id' => $this->package->id,
        ]);

        if (! session()->has('success')) {
            $response->dumpSession();
        }
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
        $this->user->user_balance = 1.50;
        $this->user->save();

        $response = $this->post(route('point-purchases.store'), [
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

        $response = $this->post(route('point-purchases.store-wallet'), [
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
        $this->user->user_balance = 0.50;
        $this->user->save();

        $response = $this->post(route('point-purchases.store-wallet'), [
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
