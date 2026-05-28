<?php

namespace Modules\ERP\app\Features\MultiBranch\Services;

use Modules\ERP\Models\BranchManager;
use App\Models\User;

class BranchPermissionResolver
{
    public function hasPermissionForBranch(User $user, int $branchId, string $permission = null): bool
    {
        // Global admin check
        if ($user->hasRole('tenant_admin')) {
            return true;
        }

        // Branch specific manager check
        $manager = BranchManager::where('user_id', $user->id)
            ->where('branch_id', $branchId)
            ->first();

        if (!$manager) {
            return false;
        }

        // If specific permission check is required inside a branch context, handle here
        return true; 
    }
}
