<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminUserUILinksTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure admin role exists
        if (Role::where('name', 'admin')->doesntExist()) {
            Role::create(['name' => 'admin']);
        }

        // Create an admin user
        $this->admin = User::factory()->create([
            'email' => 'admin_tester@test.com',
        ]);
        $this->admin->assignRole('admin');

        // Create a regular user
        $this->clientUser = User::factory()->create([
            'email' => 'client_tester@test.com',
        ]);
    }

    public function test_admin_can_view_users_index_with_search()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
            'page' => 1,
            'search' => 'dina'
        ]));
        
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_profile()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.show', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_edit_page()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.edit', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_access_login_as_endpoint()
    {
        // The route list has a GET endpoint for loginas which returns an Inertia page
        $response = $this->actingAs($this->admin)->get(route('admin.users.loginas', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_reset_user_password()
    {
        // The reset password action in the UI
        $response = $this->actingAs($this->admin)->get(route('admin.users.reset-password', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_referrals()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.referrals', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_files()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.files', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_reports()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.reports', $this->clientUser->id));
        $response->assertStatus(200);
    }
}
