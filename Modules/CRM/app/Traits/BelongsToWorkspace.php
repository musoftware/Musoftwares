<?php

namespace Modules\CRM\app\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Workspace;
use Modules\CRM\Infrastructure\Context\TenantContext;

trait BelongsToWorkspace
{
    /**
     * Boot the trait to automatically apply the workspace scope
     * and set the workspace_id on creation.
     */
    protected static function bootBelongsToWorkspace()
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            $workspaceId = app(TenantContext::class)->getWorkspaceId();

            if ($workspaceId) {
                $builder->where($builder->getModel()->getTable() . '.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($model) {
            if (!$model->workspace_id) {
                $workspaceId = app(TenantContext::class)->getWorkspaceId();
                
                if ($workspaceId) {
                    $model->workspace_id = $workspaceId;
                }
            }
        });
    }

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}
