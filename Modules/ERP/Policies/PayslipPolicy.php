<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\Payslip;
use Illuminate\Foundation\Auth\User;

class PayslipPolicy
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

    public function view(User $user, Payslip $payslip)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $payslip->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Payslip $payslip)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $payslip->tenant_id === $tenant->id;
    }

    public function delete(User $user, Payslip $payslip)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $payslip->tenant_id === $tenant->id;
    }
}
