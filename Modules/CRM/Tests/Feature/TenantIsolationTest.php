<?php

namespace Modules\CRM\Tests\Feature;

use Modules\CRM\Tests\Support\BaseTenantTestCase;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Models\Lead;
use Modules\CRM\Infrastructure\Context\TenantContext;

class TenantIsolationTest extends BaseTenantTestCase
{
    public function test_models_automatically_scope_to_tenant_context()
    {
        // $this->workspace is automatically set up by BaseTenantTestCase
        $workspace2 = Workspace::forceCreate(['user_id' => $this->adminUser->id, 'name' => 'Other Corp']);

        // Create a lead in the current tenant's workspace
        $lead1 = Lead::create(['name' => 'Lead A', 'status' => 'new', 'email' => 'lead_a@example.com', 'message' => 'test message']); // Workspace ID should be injected by trait
        
        // Create a lead in another workspace (bypassing scope for setup)
        $lead2 = Lead::withoutGlobalScopes()->forceCreate(['workspace_id' => $workspace2->id, 'name' => 'Lead B', 'email' => 'lead_b@example.com', 'message' => 'test message']);

        $leads = Lead::all();
        
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead A', $leads->first()->name);
        $this->assertEquals($this->workspace->id, $leads->first()->workspace_id);

        // Switch context to workspace 2
        $this->setTenantContext($workspace2->id);
        
        $leads = Lead::all();
        
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead B', $leads->first()->name);
    }

    public function test_jobs_maintain_tenant_isolation_without_session()
    {
        // Clear session to simulate a CLI / Queue environment
        session()->flush();
        
        // Assert we still have the context via the container
        $workspaceId = app(TenantContext::class)->getWorkspaceId();
        $this->assertEquals($this->workspace->id, $workspaceId);

        $lead = Lead::create([
            'name' => 'CLI Lead',
            'status' => 'new',
            'email' => 'cli_lead@example.com',
            'message' => 'test message'
        ]);

        $this->assertEquals($this->workspace->id, $lead->workspace_id);
    }
}
