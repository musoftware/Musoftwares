<?php

namespace Modules\CRM\app\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\CRM\Models\Workspace;

trait BelongsToWorkspace
{
    /**
     * Boot the trait to automatically apply the workspace scope
     * and set the workspace_id on creation.
     */
    protected static function bootBelongsToWorkspace()
    {
        static::addGlobalScope('workspace', function (Builder $builder) {
            if (auth()->check() && request()->routeIs('crm.*')) {
                // Determine active workspace from session or default to the user's first workspace
                $workspaceId = session('crm_workspace_id');
                if (!$workspaceId && auth()->user()) {
                    // Fallback to first workspace they have access to
                    $workspace = Workspace::where('user_id', auth()->id())
                        ->orWhereHas('users', fn($q) => $q->where('users.id', auth()->id()))
                        ->first();
                    $workspaceId = $workspace?->id;
                }

                if ($workspaceId) {
                    $builder->where($builder->getModel()->getTable() . '.workspace_id', $workspaceId);
                }
            }
        });

        static::creating(function ($model) {
            if (!$model->workspace_id && auth()->check()) {
                $workspaceId = session('crm_workspace_id');
                if (!$workspaceId && auth()->user()) {
                    $workspace = Workspace::where('user_id', auth()->id())
                        ->orWhereHas('users', fn($q) => $q->where('users.id', auth()->id()))
                        ->first();
                    $workspaceId = $workspace?->id;
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
