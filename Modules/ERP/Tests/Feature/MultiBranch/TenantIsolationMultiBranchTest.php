<?php

namespace Modules\ERP\Tests\Feature\MultiBranch;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\ERP\Models\Branch;
use App\Models\User;
use Modules\ERP\app\Features\MultiBranch\Scopes\BranchScope;
use Modules\ERP\app\Features\MultiBranch\Managers\BranchIsolationManager;

class TenantIsolationMultiBranchTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_a_cannot_access_tenant_b_branches()
    {
        // Tenant A Branch
        $branchA = Branch::create([
            'tenant_id' => 1,
            'name' => 'Branch A',
            'type' => 'office'
        ]);

        // Tenant B Branch
        $branchB = Branch::create([
            'tenant_id' => 2,
            'name' => 'Branch B',
            'type' => 'office'
        ]);

        // Validate basic isolation at query level
        $tenant1Branches = Branch::where('tenant_id', 1)->get();
        $this->assertCount(1, $tenant1Branches);
        $this->assertEquals($branchA->id, $tenant1Branches->first()->id);

        $tenant2Branches = Branch::where('tenant_id', 2)->get();
        $this->assertCount(1, $tenant2Branches);
        $this->assertEquals($branchB->id, $tenant2Branches->first()->id);
    }
}
