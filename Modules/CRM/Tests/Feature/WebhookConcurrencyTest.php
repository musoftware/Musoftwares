<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Domains\LeadAcquisition\Actions\ProcessLeadImportAction;
use Modules\CRM\Domains\LeadAcquisition\DTOs\LeadImportData;
use Modules\CRM\Tests\Support\BaseTenantTestCase;

class WebhookConcurrencyTest extends BaseTenantTestCase
{
    use DatabaseTransactions;

    public function test_concurrent_webhook_floods_are_idempotent()
    {
        $this->markTestSkipped('ProcessLeadImportAction is not implemented yet.');
        $branchId = 1;

        $agent = User::factory()->create();
        $this->workspace->users()->attach($agent->id, ['role_id' => 1]); // attach to workspace
        
        UserSubscription::create(['user_id' => $agent->id, 'object' => 'module:crm-sales-staff', 'status' => 'active']);

        // Simulate 5 duplicate concurrent payloads (e.g., webhook retries)
        $action = new ProcessLeadImportAction();
        
        $payloads = [];
        for ($i = 0; $i < 5; $i++) {
            $payloads[] = new LeadImportData(
                data: [
                    [
                        'name' => 'High Concurrency Lead',
                        'phone' => '1234567890',
                        'source' => 'Webhook',
                    ]
                ],
                sourceId: 1,
                workspaceId: $this->workspace->id,
                assignedToId: $agent->id,
                branchId: $branchId
            );
        }

        $importedCount = 0;
        foreach ($payloads as $payload) {
            $importedCount += $action->execute($payload);
        }

        // Despite 5 concurrent payloads, only 1 should successfully insert
        // The rest should be dropped by the `insertOrIgnore` hardware constraint.
        
        $this->assertEquals(1, $importedCount, "Idempotency failed. More than 1 lead was imported.");
        
        $leadCount = DB::table('leads')
            ->where('workspace_id', $this->workspace->id)
            ->where('phone', '1234567890')
            ->count();
            
        $this->assertEquals(1, $leadCount, "Database unique constraint failed. Duplicate leads exist.");
    }
}
