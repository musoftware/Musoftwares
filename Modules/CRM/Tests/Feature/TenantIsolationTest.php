<?php

namespace Modules\CRM\Tests\Feature;

use Tests\TestCase;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Models\Lead;
use App\Models\User;
use Modules\CRM\Infrastructure\Context\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_automatically_scope_to_tenant_context()
    {
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();

        // Create leads bypassing scope
        $lead1 = Lead::withoutGlobalScopes()->factory()->create(['workspace_id' => $workspace1->id, 'name' => 'Lead A']);
        $lead2 = Lead::withoutGlobalScopes()->factory()->create(['workspace_id' => $workspace2->id, 'name' => 'Lead B']);

        // Set context to workspace 1
        app(TenantContext::class)->setWorkspaceId($workspace1->id);
        
        $leads = Lead::all();
        
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead A', $leads->first()->name);

        // Switch context to workspace 2
        app(TenantContext::class)->setWorkspaceId($workspace2->id);
        
        $leads = Lead::all();
        
        $this->assertCount(1, $leads);
        $this->assertEquals('Lead B', $leads->first()->name);
    }

    public function test_jobs_maintain_tenant_isolation_without_session()
    {
        // Session should not be required for the model to fetch context
        $workspace = Workspace::factory()->create();
        app(TenantContext::class)->setWorkspaceId($workspace->id);

        $lead = Lead::create([
            'name' => 'Test Lead',
            'status' => 'new'
        ]);

        $this->assertEquals($workspace->id, $lead->workspace_id);
    }
}
