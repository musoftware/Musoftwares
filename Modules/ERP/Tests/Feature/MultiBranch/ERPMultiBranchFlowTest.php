<?php

namespace Modules\ERP\Tests\Feature\MultiBranch;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Branch;
use App\Models\User;
use Modules\ERP\app\Features\MultiBranch\Services\MultiBranchService;
use Modules\ERP\app\Features\MultiBranch\Services\ERPMultiBranchLimitsService;

class ERPMultiBranchFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_multi_branch_lifecycle()
    {
        $tenantId = 1;
        $user = User::factory()->create();
        
        // Mock the limits service
        $limitsMock = \Mockery::mock(ERPMultiBranchLimitsService::class);
        $limitsMock->shouldReceive('checkUsage')->andReturn(true);
        $limitsMock->shouldReceive('increaseUsage')->andReturnNull();
        $this->app->instance(ERPMultiBranchLimitsService::class, $limitsMock);

        // Service initialization
        $service = new MultiBranchService($limitsMock);
        
        // 1. Create Branch
        $branch = $service->createBranch([
            'name' => 'HQ Branch',
            'type' => 'office',
            'timezone' => 'UTC'
        ], $tenantId);

        $this->assertDatabaseHas('erp_branches', [
            'tenant_id' => $tenantId,
            'name' => 'HQ Branch',
            'type' => 'office'
        ]);

        // 2. Archive Branch
        $service->archiveBranch($branch);

        $this->assertDatabaseHas('erp_branches', [
            'id' => $branch->id,
            'status' => 'archived'
        ]);
        $this->assertSoftDeleted('erp_branches', [
            'id' => $branch->id
        ]);
    }
}
