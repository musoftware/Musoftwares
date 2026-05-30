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
            // Note: We no longer restrict solely to request()->routeIs('crm.*')
            // because background jobs running without a request also need tenant isolation.
            $workspaceId = app(TenantContext::class)->getWorkspaceId();

            // If we're strictly inside the CRM and have no context, but auth exists, attempt fallback
            if (!$workspaceId && auth()->check() && !app()->runningInConsole()) {
                $workspaceId = session('crm_workspace_id');
                if (!$workspaceId) {
                    $workspace = Workspace::where('user_id', auth()->id())
                        ->orWhereHas('users', fn($q) => $q->where('users.id', auth()->id()))
                        ->first();
                    $workspaceId = $workspace?->id;
                }
                
                if ($workspaceId) {
                    app(TenantContext::class)->setWorkspaceId($workspaceId);
                }
            }

            if ($workspaceId) {
                $builder->where($builder->getModel()->getTable() . '.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($model) {
            if (!$model->workspace_id) {
                $workspaceId = app(TenantContext::class)->getWorkspaceId();
                
                // Fallback for UI if context was lost somehow
                if (!$workspaceId && auth()->check() && !app()->runningInConsole()) {
                    $workspaceId = session('crm_workspace_id');
                    if (!$workspaceId) {
                        $workspace = Workspace::where('user_id', auth()->id())
                            ->orWhereHas('users', fn($q) => $q->where('users.id', auth()->id()))
                            ->first();
                        $workspaceId = $workspace?->id;
                    }
                }
                
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
