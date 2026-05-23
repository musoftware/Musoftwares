<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TeamMember;
use Modules\ERP\Models\TenantClient;
use Tests\TestCase;

class TeamAuthAndOversightTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'database/migrations',
                'Modules/Booking/database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
                'Modules/Freelance/Database/Migrations',
                'Modules/Intelligence/database/migrations',
                'Modules/Marketplace/Database/Migrations',
                'Modules/Tools/Database/Migrations',
            ]
        ]);
    }

    public function test_team_member_can_login_with_correct_credentials(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $teamMember = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Staff',
            'email' => 'john@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->post('/erp/team/login', [
            'email' => 'john@acme.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('erp.dashboard'));
        $this->assertTrue(Auth::guard('erp_team')->check());
        $this->assertEquals($teamMember->id, Auth::guard('erp_team')->id());
        $this->assertEquals($tenant->id, session('tenant_id'));
        $this->assertEquals($teamMember->id, session('erp_team_member_id'));
    }

    public function test_team_member_cannot_login_with_incorrect_credentials(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $teamMember = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Staff',
            'email' => 'john@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'active',
        ]);

        $response = $this->post('/erp/team/login', [
            'email' => 'john@acme.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('erp_team')->check());
    }

    public function test_suspended_team_member_cannot_login(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $teamMember = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Suspended',
            'email' => 'john@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'suspended',
        ]);

        $response = $this->post('/erp/team/login', [
            'email' => 'john@acme.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('erp_team')->check());
    }

    public function test_team_member_can_logout(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $teamMember = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Staff',
            'email' => 'john@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'active',
        ]);

        Auth::guard('erp_team')->login($teamMember);
        session(['tenant_id' => $tenant->id, 'erp_team_member_id' => $teamMember->id]);

        $response = $this->post('/erp/team/logout');

        $response->assertRedirect(route('erp.team.login'));
        $this->assertFalse(Auth::guard('erp_team')->check());
        $this->assertNull(session('tenant_id'));
        $this->assertNull(session('erp_team_member_id'));
    }

    public function test_team_member_session_bridging_on_dashboard(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $teamMember = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Staff',
            'email' => 'john@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'active',
        ]);

        // Access dashboard authenticated as erp_team guard user
        $response = $this->actingAs($teamMember, 'erp_team')
            ->get('/erp/dashboard');

        if ($response->status() === 302) {
            dd($response->headers->get('Location'));
        }

        $response->assertStatus(200);

        // Verify web guard was bridged to the tenant owner
        $this->assertTrue(Auth::guard('web')->check());
        $this->assertEquals($owner->id, Auth::guard('web')->id());
    }

    public function test_manager_role_can_mutate_clients(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $manager = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager Staff',
            'email' => 'manager@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'manager',
            'status' => 'active',
        ]);

        $response = $this->actingAs($manager, 'erp_team')
            ->post('/erp/clients', [
                'name' => 'New Client',
                'email' => 'client@new.com',
                'phone' => '12345678',
                'company' => 'New Co',
                'currency' => 'USD',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('erp_tenant_clients', [
            'tenant_id' => $tenant->id,
            'name' => 'New Client',
            'email' => 'client@new.com',
        ]);
    }

    public function test_member_role_cannot_mutate_clients_but_can_mutate_tasks(): void
    {
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        $member = TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'Member Staff',
            'email' => 'member@acme.com',
            'password' => Hash::make('secret123'),
            'role' => 'member',
            'status' => 'active',
        ]);

        // Attempting to create a client should fail (403 Forbidden)
        $response = $this->actingAs($member, 'erp_team')
            ->postJson('/erp/clients', [
                'name' => 'Illegal Client',
                'email' => 'client@illegal.com',
                'phone' => '12345678',
                'company' => 'New Co',
                'currency' => 'USD',
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('erp_tenant_clients', [
            'email' => 'client@illegal.com',
        ]);

        // But creating a task should succeed
        $response = $this->actingAs($member, 'erp_team')
            ->post('/erp/tasks', [
                'task_name' => 'Important Task',
                'task_description' => 'A task created by team member',
                'status' => 'open',
                'priority' => 'normal',
                'due_date' => now()->addDays(2)->toDateString(),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('erp_tasks', [
            'tenant_id' => $tenant->id,
            'task_name' => 'Important Task',
        ]);
    }

    public function test_admin_can_access_oversight_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        // Let's create a tenant to view
        $owner = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);
        $tenant = Tenant::create([
            'user_id' => $owner->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        // Access index page
        $response = $this->actingAs($admin)
            ->get('/admin/erp');

        $response->assertStatus(200);

        // Access show page
        $response = $this->actingAs($admin)
            ->get("/admin/erp/{$tenant->id}");

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_oversight_pages(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email_verified_at' => now(),
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)
            ->get('/admin/erp');

        $this->assertTrue(in_array($response->status(), [302, 403]));
    }
}
