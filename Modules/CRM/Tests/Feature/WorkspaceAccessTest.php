<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CRM\Tests\Support\BaseTenantTestCase;

class WorkspaceAccessTest extends BaseTenantTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Notification::fake();
    }

    public function test_telesales_agent_cannot_access_manager_workspace()
    {
        $agent = User::factory()->create(['onboarding_completed' => true]);
        $this->workspace->users()->attach($agent->id, ['role_id' => 1]); // role logic can be adjusted later if needed
        
        // Give base CRM subscription and sales-staff addon
        \Illuminate\Support\Facades\DB::table('user_subscriptions')->insert([
            [
                'user_id' => $agent->id,
                'object' => 'crm',
                'status' => 'active',
                'expires_at' => now()->addYear()
            ],
            [
                'user_id' => $agent->id,
                'object' => 'crm-sales-staff',
                'status' => 'active',
                'expires_at' => now()->addYear()
            ]
        ]);

        $this->actingAs($agent);

        // Can access telesales
        $response = $this->withSession(['crm_workspace_id' => $this->workspace->id])->get(route('crm.workspaces.telesales'));
        $response->assertStatus(200);

        // Cannot access manager
        $response = $this->withSession(['crm_workspace_id' => $this->workspace->id])->get(route('crm.workspaces.manager'));
        $response->assertStatus(403);
    }

    public function test_manager_can_access_manager_workspace()
    {
        \Illuminate\Support\Facades\DB::table('crm_roles')->insertOrIgnore([
            'id' => 2,
            'workspace_id' => $this->workspace->id,
            'name' => 'Manager'
        ]);

        $manager = User::factory()->create(['onboarding_completed' => true]);
        $this->workspace->users()->attach($manager->id, ['role_id' => 2]);
        
        // Give manager addon and base crm
        \Illuminate\Support\Facades\DB::table('user_subscriptions')->insert([
            [
                'user_id' => $manager->id,
                'object' => 'crm',
                'status' => 'active',
                'expires_at' => now()->addYear()
            ],
            [
                'user_id' => $manager->id,
                'object' => 'crm-sales-management',
                'status' => 'active',
                'expires_at' => now()->addYear()
            ]
        ]);

        $this->actingAs($manager);

        // Can access manager
        $response = $this->withSession(['crm_workspace_id' => $this->workspace->id])->get(route('crm.workspaces.manager'));
        $response->assertStatus(200);
    }
}
