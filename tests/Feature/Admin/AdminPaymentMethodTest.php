<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\UserPaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_payment_methods_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.payment-methods.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_payment_methods_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.payment-methods.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_payment_method_show(): void
    {
        $paymentMethod = new UserPaymentMethod();
        $paymentMethod->user_id = $this->clientUser->id;
        $paymentMethod->status = 'pending';
        $paymentMethod->type = 'bank';
        $paymentMethod->currency_id = 1;
        $paymentMethod->save();

        $response = $this->actingAs($this->admin)->get(route('admin.payment-methods.show', $paymentMethod));
        $response->assertStatus(200);
    }

    public function test_admin_can_update_payment_method_status(): void
    {
        $paymentMethod = new UserPaymentMethod();
        $paymentMethod->user_id = $this->clientUser->id;
        $paymentMethod->status = 'pending';
        $paymentMethod->type = 'bank';
        $paymentMethod->currency_id = 1;
        $paymentMethod->save();

        $response = $this->actingAs($this->admin)->put(route('admin.payment-methods.update', $paymentMethod), [
            'status' => 'active'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEquals('active', $paymentMethod->fresh()->status);
    }
}
