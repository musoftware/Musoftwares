<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Domains\SalesOperations\Actions\AssignLeadRoundRobinAction;
use Modules\CRM\Tests\Support\BaseTenantTestCase;

class LeadAssignmentTest extends BaseTenantTestCase
{
    use DatabaseTransactions;

    public function test_lead_is_assigned_via_round_robin()
    {
        $branchId = 1;

        $agent1 = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $branchId,
            'last_assigned_lead_at' => now()->subMinutes(10)
        ]);
        UserSubscription::factory()->create(['user_id' => $agent1->id, 'module_id' => 'crm-sales-staff']);

        $agent2 = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $branchId,
            'last_assigned_lead_at' => now()->subMinutes(5) // agent 2 got a lead more recently
        ]);
        UserSubscription::factory()->create(['user_id' => $agent2->id, 'module_id' => 'crm-sales-staff']);

        $leadId = DB::table('leads')->insertGetId([
            'tenant_id' => $this->tenant->id,
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
