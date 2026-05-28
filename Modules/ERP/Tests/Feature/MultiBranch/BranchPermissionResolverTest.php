<?php

namespace Modules\ERP\Tests\Feature\MultiBranch;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Branch;
use Modules\ERP\Models\BranchManager;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Modules\ERP\app\Features\MultiBranch\Services\BranchPermissionResolver;

class BranchPermissionResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_has_global_access()
    {
        $user = User::factory()->create();
        // Assuming role creation for testing
        Role::firstOrCreate(['name' => 'tenant_admin']);
        $user->assignRole('tenant_admin');
        
        $resolver = new BranchPermissionResolver();
        $this->assertTrue($resolver->hasPermissionForBranch($user, 999));
    }

    public function test_branch_manager_has_access_to_assigned_branch()
    {
        $user = User::factory()->create();
        $branch = Branch::create(['tenant_id' => 1, 'name' => 'Branch A']);
        
        BranchManager::create([
            'tenant_id' => 1,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'assigned_at' => now(),
        ]);
        
        $resolver = new BranchPermissionResolver();
        $this->assertTrue($resolver->hasPermissionForBranch($user, $branch->id));
        $this->assertFalse($resolver->hasPermissionForBranch($user, 999));
    }
}
