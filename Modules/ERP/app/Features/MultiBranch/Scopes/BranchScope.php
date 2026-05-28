<?php

namespace Modules\ERP\app\Features\MultiBranch\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\ERP\app\Features\MultiBranch\Managers\BranchIsolationManager;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        $isolationManager = app(BranchIsolationManager::class);

        if ($isolationManager->hasActiveBranch()) {
            $builder->where($model->getTable() . '.branch_id', $isolationManager->getActiveBranchId());
        }
    }
}
