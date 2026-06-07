<?php

namespace Modules\ERP\Tests\Feature\MultiBranch;

use Tests\TestCase;
use Modules\ERP\app\Features\MultiBranch\Services\MultiBranchService;
use Modules\ERP\app\Features\MultiBranch\Services\ERPMultiBranchLimitsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MultiBranchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_branch_if_under_limit()
    {
        $tenantId = 1;
        
        $limitsMock = \Mockery::mock(ERPMultiBranchLimitsService::class);
        $limitsMock->shouldReceive('checkUsage')->once()->with($tenantId, 'max_branches')->andReturn(true);
        $limitsMock->shouldReceive('increaseUsage')->once()->with($tenantId, 'max_branches')->andReturnNull();
        
        $service = new MultiBranchService($limitsMock);
        
        $branch = $service->createBranch([
            'name' => 'Test Branch',
            'type' => 'retail'
        ], $tenantId);
        
        $this->assertEquals('Test Branch', $branch->name);
        $this->assertEquals($tenantId, $branch->tenant_id);
    }
    
    public function test_throws_exception_if_over_limit()
    {
        $tenantId = 1;
        
        $limitsMock = \Mockery::mock(ERPMultiBranchLimitsService::class);
        $limitsMock->shouldReceive('checkUsage')->once()->with($tenantId, 'max_branches')->andReturn(false);
        
        $service = new MultiBranchService($limitsMock);
        
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Maximum number of branches reached for this tenant.');
        
        $service->createBranch([
            'name' => 'Test Branch',
            'type' => 'retail'
        ], $tenantId);
    }
}
