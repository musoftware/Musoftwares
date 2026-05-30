<?php

namespace Modules\CRM\Infrastructure\Authorization;

use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Branch;

trait HasEnterpriseVisibility
{
    /**
     * Apply the enterprise visibility scope based on the user's role and branch context.
     *
     * @param Builder $query
     * @param \App\Models\User $user
     * @param string $permission (e.g., 'leads.view')
     * @return Builder
     */
    public function scopeApplyVisibility(Builder $query, \App\Models\User $user, string $permission): Builder
    {
        // 1. If user is an ERP admin/owner, they see everything in the workspace.
        // We assume TenantContext is already applied as a global scope or via query base.
        if ($user->isErpAdmin()) {
            return $query;
        }

        // 2. Resolve the user's branches and roles
        $userBranches = \Modules\CRM\Models\Branch::whereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id);
        })->with(['users' => function ($q) use ($user) {
            $q->where('users.id', $user->id);
        }])->get();
        
        if ($userBranches->isEmpty()) {
            // No branch assigned? Only see records explicitly assigned to them.
            return $this->scopeToPersonal($query, $user->id);
        }

        // 3. Calculate max visibility scope across all user's branches.
        // E.g., 'global', 'branch_deep', 'branch_local', 'team', 'personal'
        $maxScope = 'personal'; 
        $branchIdsDeep = [];
        $branchIdsLocal = [];

        foreach ($userBranches as $branch) {
            $userRelation = $branch->users->first();
            if (!$userRelation) continue;

            $roleId = $userRelation->pivot->role_id;
            $role = \Modules\CRM\Models\Role::find($roleId);
            
            if (!$role) continue;

            // Pseudo-logic: In a real system, you'd lookup the specific visibility scope for this permission.
            // For now, we simulate basic hierarchy interpretation.
            $scope = 'personal'; 
            if ($role->is_system && $role->name === 'Manager') {
                $scope = 'branch_deep';
            } elseif ($role->name === 'Agent') {
                $scope = 'personal';
            }

            if ($scope === 'branch_deep') {
                $branchIdsDeep[] = $branch->id;
                // Add all descendants
                $descendants = $branch->descendants()->pluck('id')->toArray();
                $branchIdsDeep = array_merge($branchIdsDeep, $descendants);
                $maxScope = 'branch_deep';
            } elseif ($scope === 'branch_local' && $maxScope !== 'branch_deep') {
                $branchIdsLocal[] = $branch->id;
                $maxScope = 'branch_local';
            }
        }

        // 4. Apply the queries based on calculated scopes.
        if ($maxScope === 'branch_deep') {
            return $query->whereIn('branch_id', array_unique($branchIdsDeep));
        }

        if ($maxScope === 'branch_local') {
            return $query->whereIn('branch_id', array_unique($branchIdsLocal));
        }

        // Default: Personal
        return $this->scopeToPersonal($query, $user->id);
    }

    /**
     * Scope the query to records explicitly assigned to or created by the user.
     */
    protected function scopeToPersonal(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            if (in_array('assigned_to', $this->getFillable())) {
                $q->where('assigned_to', $userId);
            }
            if (in_array('user_id', $this->getFillable())) {
                $q->orWhere('user_id', $userId);
            }
        });
    }
}
