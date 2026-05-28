<?php

namespace Modules\ERP\app\Features\MultiBranch\Services;

use Modules\ERP\Models\Branch;
use Illuminate\Support\Facades\DB;
use App\Helpers\UsageHelper;

class MultiBranchService
{
    protected ERPMultiBranchLimitsService $limitsService;

    public function __construct(ERPMultiBranchLimitsService $limitsService)
    {
        $this->limitsService = $limitsService;
    }

    public function createBranch(array $data, int $tenantId): Branch
    {
        // Check limits
        if (!$this->limitsService->checkUsage($tenantId, 'max_branches')) {
            throw new \Exception('Maximum number of branches reached for this tenant.');
        }

        DB::beginTransaction();
        try {
            $branch = Branch::create(array_merge($data, ['tenant_id' => $tenantId]));
            UsageHelper::increaseUsage($tenantId, 'max_branches');
            
            DB::commit();
            return $branch;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function archiveBranch(Branch $branch): void
    {
        $branch->status = 'archived';
        $branch->save();
        $branch->delete();
    }
}
