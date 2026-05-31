<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_update_user_role_directly(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/update-role", [
                'role' => 'admin',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', __('erp.role_updated_success'));

        $this->assertTrue($this->clientUser->fresh()->hasRole('admin'));
        $this->assertFalse($this->clientUser->fresh()->hasRole('client'));
    }

    public function test_cannot_update_user_role_with_invalid_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->clientUser->id}/update-role", [
                'role' => 'invalid_role_name',
            ]);

        $response->assertSessionHasErrors('role');
        $this->assertTrue($this->clientUser->fresh()->hasRole('client'));
    }

    public function test_admin_cannot_update_own_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->post("/admin/users/{$this->admin->id}/update-role", [
                'role' => 'client',
            ]);

        $response->assertSessionHasErrors('role');
        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }
}
