<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentMethodControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_payment_methods_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.payment-methods.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_payment_methods_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.payment-methods.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_payment_method()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $paymentMethod = UserPaymentMethod::forceCreate([
            'user_id' => $client->id,
            'status' => 'pending',
            'mobile' => '01000000000',
            'bank_name' => 'Test Bank',
            'type' => 'bank',
            'bank_number' => '123456789',
            'currency_id' => \App\Models\Currency::first()->id ?? 1
        ]);

        $response = $this->actingAs($admin)->get(route('admin.payment-methods.show', $paymentMethod->id));

        $response->assertSuccessful();
    }

    public function test_admin_can_update_payment_method_status()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $paymentMethod = UserPaymentMethod::forceCreate([
            'user_id' => $client->id,
            'status' => 'pending',
            'mobile' => '01000000000',
            'bank_name' => 'Test Bank',
            'type' => 'bank',
            'bank_number' => '123456789',
            'currency_id' => \App\Models\Currency::first()->id ?? 1
        ]);

        $response = $this->actingAs($admin)->put(route('admin.payment-methods.update', $paymentMethod->id), [
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_payment_methods', [
            'id' => $paymentMethod->id,
            'status' => 'active',
        ]);
    }
}
