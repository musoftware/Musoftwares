<?php

namespace Modules\CRM\Tests\Support;

use Modules\CRM\Models\Branch;

abstract class BaseBranchTestCase extends BaseTenantTestCase
{
    protected Branch $primaryBranch;
    protected Branch $secondaryBranch;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBranches();
    }

    /**
     * Create default branches inside the main workspace.
     */
    protected function setUpBranches(): void
    {
        $this->primaryBranch = Branch::factory()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Main Branch'
        ]);

        $this->secondaryBranch = Branch::factory()->create([
            'workspace_id' => $this->workspace->id,
            'name' => 'Secondary Branch'
        ]);

        // Default to running tests within the primary branch context
        $this->setBranchContext($this->primaryBranch->id);
    }

    /**
     * Set the active branch context.
     */
    protected function setBranchContext(int $branchId): void
    {
        app(\Modules\CRM\Infrastructure\Context\TenantContext::class)->setBranchId($branchId);
        session(['crm_branch_id' => $branchId]);
    }

    /**
     * Switch context rapidly between branches.
     */
    protected function switchBranch(Branch $branch): void
    {
        $this->setBranchContext($branch->id);
    }
}
