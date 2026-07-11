<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountantRoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'accountant']);
        Role::firstOrCreate(['name' => 'client']);
    }

    public function test_admin_can_access_all_admin_routes()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->markEmailAsVerified();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        // Dashboard (admin middleware)
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);

        // Users (admin middleware)
        $response = $this->get('/admin/users');
        $response->assertStatus(200);

        // Invoices (accountant middleware which includes admin)
        $response = $this->get('/admin/invoices');
        $response->assertStatus(200);

        // Finance (accountant middleware which includes admin)
        $response = $this->get('/admin/finance');
        $response->assertStatus(200);
    }

    public function test_accountant_can_only_access_financial_routes()
    {
        $accountant = User::factory()->create(['onboarding_completed' => true]);
        $accountant->markEmailAsVerified();
        $accountant->assignRole('accountant');

        $this->actingAs($accountant);

        // Financial routes should be accessible (200 OK)
        $response = $this->get('/admin/invoices');
        $response->assertStatus(200);

        $response = $this->get('/admin/finance');
        $response->assertStatus(200);

        $response = $this->get('/admin/payment-methods');
        $response->assertStatus(200);

        // Core admin routes should be forbidden (403)
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);

        $response = $this->get('/admin/users');
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_access_any_admin_routes()
    {
        $user = User::factory()->create(['onboarding_completed' => true]);
        $user->markEmailAsVerified();
        $user->assignRole('client');

        $this->actingAs($user);

        $response = $this->get('/admin/dashboard');
        $response->assertStatus(403);

        $response = $this->get('/admin/invoices');
        $response->assertStatus(403);

        $response = $this->get('/admin/finance');
        $response->assertStatus(403);
    }
}
