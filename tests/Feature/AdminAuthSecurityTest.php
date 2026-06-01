<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminAuthSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $user->assignRole('client');

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'onboarding_completed' => true,
        ]);
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        // Note: It might return 200 (Inertia response) or redirect depending on setup, 
        // but it should NOT return 403 or redirect to login.
        $response->assertSuccessful();
    }
}
