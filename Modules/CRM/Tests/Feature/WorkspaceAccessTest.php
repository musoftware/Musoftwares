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
        $agent = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Give only sales-staff addon
        UserSubscription::factory()->create([
            'user_id' => $agent->id,
            'module_id' => 'crm-sales-staff'
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
        $manager = User::factory()->create(['tenant_id' => $this->tenant->id]);
        
        // Give manager addon
        UserSubscription::factory()->create([
            'user_id' => $manager->id,
            'module_id' => 'crm-sales-management'
        ]);

        $this->actingAs($manager);

        // Can access manager
        $response = $this->get('/erp/crm/workspaces/manager');
        $response->assertStatus(200);
    }
}
