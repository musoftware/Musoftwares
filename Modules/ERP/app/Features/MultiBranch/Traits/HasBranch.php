<?php

namespace Modules\ERP\app\Features\MultiBranch\Traits;

use Modules\ERP\app\Features\MultiBranch\Scopes\BranchScope;

trait HasBranch
{
    /**
     * Boot the trait for a model.
     */
    protected static function bootHasBranch()
    {
        static::addGlobalScope(new BranchScope);

        static::creating(function ($model) {
            $isolationManager = app(\Modules\ERP\app\Features\MultiBranch\Managers\BranchIsolationManager::class);
            if (!$model->branch_id && $isolationManager->hasActiveBranch()) {
                $model->branch_id = $isolationManager->getActiveBranchId();
            }
        });
    }

    public function branch()
    {
        return $this->belongsTo(\Modules\ERP\Models\Branch::class, 'branch_id');
    }
}
