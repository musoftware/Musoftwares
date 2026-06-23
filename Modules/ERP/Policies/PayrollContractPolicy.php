<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\PayrollContract;
use Illuminate\Foundation\Auth\User;

class PayrollContractPolicy
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

    public function view(User $user, PayrollContract $payrollContract)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $payrollContract->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, PayrollContract $payrollContract)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $payrollContract->tenant_id === $tenant->id;
    }

    public function delete(User $user, PayrollContract $payrollContract)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $payrollContract->tenant_id === $tenant->id;
    }
}
