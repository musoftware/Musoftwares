<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_page_and_run_migrate(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/admin/system/migrations')->assertOk();
        $this->actingAs($admin)->post('/admin/system/migrate')->assertSessionHas('success');
    }

    public function test_regular_user_and_guest_cannot_migrate(): void
    {
        $this->post('/admin/system/migrate')->assertRedirect();

        $user = User::factory()->create(['onboarding_completed' => true]);
        $this->actingAs($user)->post('/admin/system/migrate')->assertStatus(403);
        $this->actingAs($user)->get('/admin/system/migrations')->assertStatus(403);
    }
}
