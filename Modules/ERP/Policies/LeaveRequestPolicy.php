<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\LeaveRequest;
use Illuminate\Foundation\Auth\User;

class LeaveRequestPolicy
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

    public function view(User $user, LeaveRequest $leaveRequest)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $leaveRequest->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, LeaveRequest $leaveRequest)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $leaveRequest->tenant_id === $tenant->id;
    }

    public function delete(User $user, LeaveRequest $leaveRequest)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $leaveRequest->tenant_id === $tenant->id;
    }
}
