<?php

namespace Modules\CRM\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Models\CrmTeamMember;
use App\Models\User;

class CrmTeamTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected $user;
    protected $workspace;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'onboarding_completed' => true,
        ]);

        // Give the user CRM + team subscriptions
        \App\Models\UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        \App\Models\UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp-team-members',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Create CRM workspace (no ERP tenant needed!)
        $this->workspace = Workspace::create([
            'user_id' => $this->user->id,
            'name' => 'Default Workspace',
        ]);

        $role = \Modules\CRM\Models\Role::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Owner',
            'is_system' => true,
        ]);

        $this->workspace->users()->attach($this->user->id, [
            'role_id' => $role->id,
        ]);
    }

    // ── INDEX ──

    public function test_can_view_team_members_index()
    {
        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->get(route('crm.team-members.index'));

        $response->assertStatus(200);
    }

    // ── STORE ──

    public function test_can_add_team_member()
    {
        $payload = [
            'name' => 'Ahmed Sales',
            'email' => 'ahmed@test.com',
            'password' => 'password123',
            'role' => 'sales_agent',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->post(route('crm.team-members.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('crm_team_members', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Ahmed Sales',
            'email' => 'ahmed@test.com',
            'role' => 'sales_agent',
            'status' => 'active',
        ]);
    }

    public function test_can_add_social_media_role()
    {
        \App\Models\UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'crm-advanced-roles',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $payload = [
            'name' => 'Sara Social',
            'email' => 'sara@test.com',
            'password' => 'password123',
            'role' => 'social_media',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->post(route('crm.team-members.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('crm_team_members', [
            'workspace_id' => $this->workspace->id,
            'name' => 'Sara Social',
            'role' => 'social_media',
        ]);
    }

    // ── CAPACITY ──

    public function test_cannot_exceed_capacity_limit()
    {
        // Create 3 active members (default capacity limit)
        for ($i = 0; $i < 3; $i++) {
            CrmTeamMember::create([
                'workspace_id' => $this->workspace->id,
                'name' => "Member $i",
                'email' => "member{$i}@test.com",
                'password' => bcrypt('password'),
                'role' => 'sales_agent',
                'status' => 'active',
                'invited_by' => $this->user->id,
                'invited_at' => now(),
            ]);
        }

        $payload = [
            'name' => 'Extra Member',
            'email' => 'extra@test.com',
            'password' => 'password123',
            'role' => 'sales_agent',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->post(route('crm.team-members.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('crm_team_members', [
            'email' => 'extra@test.com',
        ]);
    }

    // ── UPDATE ──

    public function test_can_update_team_member_role()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Member',
            'email' => 'test@test.com',
            'password' => bcrypt('password'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->put(route('crm.team-members.update', $member->id), [
                'role' => 'member',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('crm_team_members', [
            'id' => $member->id,
            'role' => 'member',
        ]);
    }

    public function test_can_suspend_team_member()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Test Member',
            'email' => 'suspend@test.com',
            'password' => bcrypt('password'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->put(route('crm.team-members.update', $member->id), [
                'role' => 'sales_agent',
                'status' => 'suspended',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('crm_team_members', [
            'id' => $member->id,
            'status' => 'suspended',
        ]);
    }

    // ── DELETE ──

    public function test_can_delete_team_member()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'To Delete',
            'email' => 'delete@test.com',
            'password' => bcrypt('password'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->delete(route('crm.team-members.destroy', $member->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('crm_team_members', [
            'id' => $member->id,
        ]);
    }

    // ── ACCESS CONTROL ──

    public function test_team_member_cannot_manage_team()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Regular Agent',
            'email' => 'agent@test.com',
            'password' => bcrypt('password'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        // Simulate team member session
        $response = $this->actingAs($this->user)
            ->withSession([
                'crm_workspace_id' => $this->workspace->id,
                'crm_team_member_id' => $member->id,
            ])
            ->get(route('crm.team-members.index'));

        $response->assertStatus(403);
    }

    // ── MODEL TESTS ──

    public function test_crm_team_member_model_roles()
    {
        $this->assertEquals('social_media', CrmTeamMember::ROLE_SOCIAL_MEDIA);

        $allRoles = CrmTeamMember::getAllRoles();
        $this->assertArrayHasKey('social_media', $allRoles);
        $this->assertArrayHasKey('member', $allRoles);
        $this->assertArrayHasKey('sales_agent', $allRoles);
        $this->assertArrayHasKey('manager', $allRoles);

        // Member role checks
        $member = new CrmTeamMember(['role' => 'social_media']);
        $this->assertTrue($member->isMember());
        $this->assertFalse($member->isManager());

        $manager = new CrmTeamMember(['role' => 'manager']);
        $this->assertTrue($manager->isManager());
        $this->assertFalse($manager->isMember());
    }

    public function test_workspace_has_team_members_relationship()
    {
        CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Agent 1',
            'email' => 'agent1@test.com',
            'password' => bcrypt('password'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $this->assertEquals(1, $this->workspace->teamMembers()->count());
    }

    // ── LOGIN / LOGOUT ──

    public function test_crm_login_page_renders()
    {
        $response = $this->get(route('crm.team.login'));
        $response->assertStatus(200);
    }

    public function test_crm_team_member_can_login()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Telesales Agent',
            'email' => 'telesales@test.com',
            'password' => bcrypt('password123'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->post(route('crm.team.login.store'), [
            'email' => 'telesales@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('crm.workspaces.telesales'));
    }

    public function test_suspended_member_cannot_login()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Suspended Agent',
            'email' => 'suspended@test.com',
            'password' => bcrypt('password123'),
            'role' => 'sales_agent',
            'status' => 'suspended',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->post(route('crm.team.login.store'), [
            'email' => 'suspended@test.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_redirects_social_media_to_leads()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Social Media Agent',
            'email' => 'social@test.com',
            'password' => bcrypt('password123'),
            'role' => 'social_media',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->post(route('crm.team.login.store'), [
            'email' => 'social@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('crm.leads.index'));
    }

    public function test_login_redirects_manager_to_manager_dashboard()
    {
        $member = CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Manager',
            'email' => 'manager@test.com',
            'password' => bcrypt('password123'),
            'role' => 'manager',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->post(route('crm.team.login.store'), [
            'email' => 'manager@test.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('crm.workspaces.manager'));
    }

    // ── WORKSPACE ISOLATION ──

    public function test_member_email_unique_per_workspace()
    {
        CrmTeamMember::create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Agent',
            'email' => 'duplicate@test.com',
            'password' => bcrypt('password'),
            'role' => 'sales_agent',
            'status' => 'active',
            'invited_by' => $this->user->id,
            'invited_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['crm_workspace_id' => $this->workspace->id])
            ->post(route('crm.team-members.store'), [
                'name' => 'Duplicate Agent',
                'email' => 'duplicate@test.com',
                'password' => 'password123',
                'role' => 'member',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_no_erp_dependency()
    {
        // Verify we can create team members without any ERP tenant
        $freshUser = User::factory()->create(['onboarding_completed' => true]);

        \App\Models\UserSubscription::create([
            'user_id' => $freshUser->id,
            'object' => 'crm',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        \App\Models\UserSubscription::create([
            'user_id' => $freshUser->id,
            'object' => 'erp-team-members',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $workspace = Workspace::create([
            'user_id' => $freshUser->id,
            'name' => 'CRM Only Workspace',
        ]);

        $role = \Modules\CRM\Models\Role::create([
            'workspace_id' => $workspace->id,
            'name' => 'Owner',
            'is_system' => true,
        ]);

        $workspace->users()->attach($freshUser->id, ['role_id' => $role->id]);

        // This user has NO ERP tenant, but CRM team should still work
        $response = $this->actingAs($freshUser)
            ->withSession(['crm_workspace_id' => $workspace->id])
            ->post(route('crm.team-members.store'), [
                'name' => 'CRM Only Agent',
                'email' => 'crmonly@test.com',
                'password' => 'password123',
                'role' => 'sales_agent',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('crm_team_members', [
            'workspace_id' => $workspace->id,
            'email' => 'crmonly@test.com',
        ]);
    }
}
