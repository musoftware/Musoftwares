<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\CRM\Tests\Support\BaseTenantTestCase;

class WorkspaceAccessTest extends BaseTenantTestCase
{
    use DatabaseTransactions;

    public function test_telesales_agent_cannot_access_manager_workspace()
    {
        $agent = User::factory()->create();
        $this->workspace->users()->attach($agent->id, ['role_id' => 1]); // role logic can be adjusted later if needed
        
        // Give only sales-staff addon
        UserSubscription::create([
            'user_id' => $agent->id,
            'object' => 'module:crm-sales-staff',
            'status' => 'active'
        ]);

        $this->actingAs($agent);

        // Can access telesales
        $response = $this->get('/erp/crm/workspaces/telesales');
        $response->assertStatus(200);

        // Cannot access manager
        $response = $this->get('/erp/crm/workspaces/manager');
        $response->assertStatus(403);
    }

    public function test_manager_can_access_manager_workspace()
    {
        $manager = User::factory()->create();
        $this->workspace->users()->attach($manager->id, ['role_id' => 1]);
        
        // Give manager addon
        UserSubscription::create([
            'user_id' => $manager->id,
            'object' => 'module:crm-sales-management',
            'status' => 'active'
        ]);

        $this->actingAs($manager);

        // Can access manager
        $response = $this->get('/erp/crm/workspaces/manager');
        $response->assertStatus(200);
    }
}
