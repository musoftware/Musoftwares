<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\Withdrawal;
use Illuminate\Foundation\Auth\User;

class WithdrawalPolicy
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

    public function view(User $user, Withdrawal $withdrawal)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $withdrawal->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Withdrawal $withdrawal)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $withdrawal->tenant_id === $tenant->id;
    }

    public function delete(User $user, Withdrawal $withdrawal)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $withdrawal->tenant_id === $tenant->id;
    }
}
