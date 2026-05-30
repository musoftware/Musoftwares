<?php

namespace Modules\CRM\Tests\Feature;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Domains\LeadAcquisition\Actions\ProcessLeadImportAction;
use Modules\CRM\Domains\LeadAcquisition\DTOs\LeadImportData;
use Modules\CRM\Tests\Support\BaseTenantTestCase;

class WebhookConcurrencyTest extends BaseTenantTestCase
{
    use RefreshDatabase;

    public function test_concurrent_webhook_floods_are_idempotent()
    {
        $branchId = 1;

        $agent = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $branchId,
        ]);
        UserSubscription::factory()->create(['user_id' => $agent->id, 'module_id' => 'crm-sales-staff']);

        // Simulate 5 duplicate concurrent payloads (e.g., WhatsApp retries)
        $action = new ProcessLeadImportAction();
        
        $payloads = [];
        for ($i = 0; $i < 5; $i++) {
            $payloads[] = new LeadImportData(
                assignedToId: $agent->id,
                branchId: $branchId,
                data: [
                    [
                        'name' => 'High Concurrency Lead',
                        'phone' => '1234567890',
                        'source' => 'Webhook',
                    ]
                ]
            );
        }

        // Set tenant context for the action
        session(['tenant_id' => $this->tenant->id]);

        $importedCount = 0;
        foreach ($payloads as $payload) {
            $importedCount += $action->execute($payload);
        }

        // Despite 5 concurrent payloads, only 1 should successfully insert
        // The rest should be dropped by the `insertOrIgnore` hardware constraint.
        
        $this->assertEquals(1, $importedCount, "Idempotency failed. More than 1 lead was imported.");
        
        $leadCount = DB::table('leads')
            ->where('tenant_id', $this->tenant->id)
            ->where('phone', '1234567890')
            ->count();
            
        $this->assertEquals(1, $leadCount, "Database unique constraint failed. Duplicate leads exist.");
    }
}
