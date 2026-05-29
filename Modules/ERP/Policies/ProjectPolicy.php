<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;
use Modules\ERP\Models\Project;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class ProjectPolicy
{
    use HandlesAuthorization;

    protected function getTenant(User $user)
    {
        $tenant = Tenant::where('user_id', $user->id)->first();
        if (!$tenant && Auth::guard('erp_team')->check()) {
            $tenant = Auth::guard('erp_team')->user()->tenant;
        }
        return $tenant;
    }

    public function viewAny(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function view(User $user, Project $project)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $project->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Project $project)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $project->tenant_id === $tenant->id;
    }

    public function delete(User $user, Project $project)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $project->tenant_id === $tenant->id;
    }
}
