<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Domains\SalesOperations\Actions\AssignLeadRoundRobinAction;
use Modules\CRM\Tests\Support\BaseTenantTestCase;

class LeadAssignmentTest extends BaseTenantTestCase
{
    use RefreshDatabase;

    public function test_lead_is_assigned_via_round_robin()
    {
        $this->markTestSkipped('AssignLeadRoundRobinAction is not implemented yet.');
        $branchId = 1;

        $agent1 = User::factory()->create([
            'tenant_id' => $this->workspace->id,
            'branch_id' => $branchId,
            'last_assigned_lead_at' => now()->subMinutes(10)
        ]);
        UserSubscription::create(['user_id' => $agent1->id, 'object' => 'module:crm-sales-staff', 'status' => 'active']);

        $agent2 = User::factory()->create([
            'tenant_id' => $this->workspace->id,
            'branch_id' => $branchId,
            'last_assigned_lead_at' => now()->subMinutes(5) // agent 2 got a lead more recently
        ]);
        UserSubscription::create(['user_id' => $agent2->id, 'object' => 'module:crm-sales-staff', 'status' => 'active']);

        $leadId = DB::table('leads')->insertGetId([
            'tenant_id' => $this->workspace->id,
            'name' => 'Test Lead',
            'pipeline_stage' => 'NEW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $action = new AssignLeadRoundRobinAction();
        $action->execute($leadId, $branchId);

        $lead = DB::table('leads')->where('id', $leadId)->first();

        // Agent 1 should get it because they waited longer (subMinutes 10 vs 5)
        $this->assertEquals($agent1->id, $lead->assigned_to_id);
        $this->assertNotNull($lead->reassigned_at);
    }
}
