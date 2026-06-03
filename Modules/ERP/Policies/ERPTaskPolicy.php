<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;
use Modules\ERP\Models\ERPTask;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class ERPTaskPolicy
{
    use HandlesAuthorization;

    protected function getTenant(User $user)
    {
        return $user->tenant;
    }

    public function viewAny(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function view(User $user, ERPTask $task)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $task->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, ERPTask $task)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $task->tenant_id === $tenant->id;
    }

    public function delete(User $user, ERPTask $task)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $task->tenant_id === $tenant->id;
    }
}
