<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\Contract;
use Illuminate\Foundation\Auth\User;

class ContractPolicy
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

    public function view(User $user, Contract $contract)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $contract->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Contract $contract)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $contract->tenant_id === $tenant->id;
    }

    public function delete(User $user, Contract $contract)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $contract->tenant_id === $tenant->id;
    }
}
