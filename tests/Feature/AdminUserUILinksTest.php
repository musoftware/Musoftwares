<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserUILinksTest extends TestCase
{
    private User $admin;
    private User $targetUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->targetUser = User::factory()->create(['role' => 'user']);
    }

    public function test_admin_users_index_with_search()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users?page=1&search=dina');
            
        $response->assertOk();
    }

    public function test_admin_user_view_profile_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id);
            
        $response->assertOk();
    }

    public function test_admin_user_edit_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/edit');
            
        $response->assertOk();
    }

    public function test_admin_user_login_as_action()
    {
        // The UI sends a POST to this exact path
        $response = $this->actingAs($this->admin)
            ->post('/admin/users/' . $this->targetUser->id . '/login-as');
            
        $this->assertTrue(in_array($response->status(), [200, 302]), "Status was " . $response->status());
    }

    public function test_admin_user_reset_password_action()
    {
        // The UI sends a POST to this exact path
        $response = $this->actingAs($this->admin)
            ->post('/admin/users/' . $this->targetUser->id . '/reset-password');
            
        $this->assertTrue(in_array($response->status(), [200, 302]), "Status was " . $response->status());
    }

    public function test_admin_user_referrals_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/referrals');
            
        $response->assertOk();
    }

    public function test_admin_user_files_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/files');
            
        $response->assertOk();
    }

    public function test_admin_user_reports_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/reports');
            
        $response->assertOk();
    }

    public function test_admin_invoices_create_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/invoices/create?user=' . $this->targetUser->id);
            
        $response->assertOk();
    }

    public function test_admin_transactions_receive_money_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/transactions/create?user=' . $this->targetUser->id . '&type=receive');
            
        $response->assertOk();
    }

    public function test_admin_transactions_send_money_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/transactions/create?user=' . $this->targetUser->id . '&type=send-money');
            
        $response->assertOk();
    }

    public function test_admin_transactions_refund_money_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/transactions/create?user=' . $this->targetUser->id . '&type=refund');
            
        $response->assertOk();
    }

    public function test_admin_user_invoices_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/invoices?user=' . $this->targetUser->id);
            
        $response->assertOk();
    }

    public function test_admin_transactions_transfer_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/transactions/transfer?user=' . $this->targetUser->id);
            
        $response->assertOk();
    }

    public function test_admin_all_transactions_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/transactions?user=' . $this->targetUser->id);
            
        $response->assertOk();
    }

    public function test_admin_user_projects_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/projects');
            
        $response->assertOk();
    }

    public function test_admin_user_tasks_assign_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/tasks/assign');
            
        $response->assertOk();
    }

    public function test_admin_user_notes_link()
    {
        $response = $this->actingAs($this->admin)
            ->get('/admin/users/' . $this->targetUser->id . '/notes');
            
        $response->assertOk();
    }
}
