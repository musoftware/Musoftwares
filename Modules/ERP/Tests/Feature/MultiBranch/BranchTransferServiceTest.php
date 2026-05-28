<?php

namespace Modules\ERP\Tests\Feature\MultiBranch;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Branch;
use App\Models\User;
use Modules\ERP\app\Features\MultiBranch\Services\BranchTransferService;

class BranchTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_and_approve_transfer()
    {
        $user = User::factory()->create();
        
        $branchA = Branch::create(['tenant_id' => 1, 'name' => 'A']);
        $branchB = Branch::create(['tenant_id' => 1, 'name' => 'B']);

        $service = new BranchTransferService();
        $transfer = $service->createTransfer(1, $branchA->id, $branchB->id, 'inventory', $user->id);

        $this->assertDatabaseHas('erp_branch_transfers', [
            'id' => $transfer->id,
            'status' => 'pending',
            'type' => 'inventory'
        ]);

        $approver = User::factory()->create();
        $service->approveTransfer($transfer, $approver->id);

        $this->assertDatabaseHas('erp_branch_transfers', [
            'id' => $transfer->id,
            'status' => 'completed',
            'approved_by' => $approver->id
        ]);
    }
}
