<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminUserPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

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
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $freshUser = $this->clientUser->fresh();
        
        $this->assertTrue($freshUser->hasRole('Admin') || $freshUser->hasRole('admin'));
        $this->assertFalse($freshUser->hasRole('Client') && $freshUser->hasRole('client'));
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
        
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $freshAdmin = $this->admin->fresh();
        $this->assertTrue($freshAdmin->hasRole('Admin') || $freshAdmin->hasRole('admin'));
    }
}
