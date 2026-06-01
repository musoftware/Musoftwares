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
        $this->withoutVite();

        // Ensure admin and client roles exist
        if (Role::where('name', 'admin')->doesntExist()) {
            Role::create(['name' => 'admin']);
        }
        if (Role::where('name', 'client')->doesntExist()) {
            Role::create(['name' => 'client']);
        }

        // Create an admin user
        $this->admin = User::factory()->create([
            'email' => 'admin_tester@test.com',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        // Create a regular user
        $this->clientUser = User::factory()->create([
            'email' => 'client_tester@test.com',
            'onboarding_completed' => true,
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
        // The endpoint performs login and redirects to the dashboard
        $response = $this->actingAs($this->admin)->get(route('admin.users.login-as', $this->clientUser->id));
        $response->assertRedirect(route('dashboard'));
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

    public function test_admin_can_view_users_create_page()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.create'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_problematic_users_page()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.problematic'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_co_work_page()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.co-work'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_earning_analyze_page()
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('Earning analyze uses UNIX_TIMESTAMP which is not supported by sqlite.');
        }

        $response = $this->actingAs($this->admin)->get(route('admin.users.earning-analyze'));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_projects()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.projects', $this->clientUser->id));
        // It redirects to admin.projects.index
        $response->assertRedirect(route('admin.projects.index', ['user_id' => $this->clientUser->id]));
    }

    public function test_admin_can_view_user_notes()
    {
        $response = $this->actingAs($this->admin)->get(route('admin.users.notes.index', $this->clientUser->id));
        $response->assertStatus(200);
    }

    public function test_admin_can_view_invoice_create_for_user()
    {
        $response = $this->actingAs($this->admin)->get('/admin/invoices/create?user=' . $this->clientUser->id);
        $response->assertRedirect();
    }

    public function test_admin_can_view_transaction_create_for_user()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions/create?user=' . $this->clientUser->id . '&type=receive');
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_invoices()
    {
        $response = $this->actingAs($this->admin)->get('/admin/invoices?client_id=' . $this->clientUser->id);
        $response->assertStatus(200);
    }

    public function test_admin_can_view_user_transactions()
    {
        $response = $this->actingAs($this->admin)->get('/admin/transactions?user=' . $this->clientUser->id);
        $response->assertStatus(200);
    }
}
