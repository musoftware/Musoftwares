<?php

namespace Modules\ERP\Tests\Feature\MultiBranch;

use Tests\TestCase;
use Modules\ERP\app\Features\MultiBranch\Managers\BranchIsolationManager;
use Illuminate\Support\Facades\Session;

class BranchIsolationManagerTest extends TestCase
{
    public function test_it_sets_and_gets_active_branch()
    {
        $manager = new BranchIsolationManager();
        $manager->setActiveBranchId(5);

        $this->assertEquals(5, $manager->getActiveBranchId());
        $this->assertTrue($manager->hasActiveBranch());
        $this->assertEquals(5, Session::get('active_branch_id'));
    }
}
