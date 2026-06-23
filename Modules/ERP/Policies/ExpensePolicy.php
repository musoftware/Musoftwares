<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\Expense;
use Illuminate\Foundation\Auth\User;

class ExpensePolicy
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

    public function view(User $user, Expense $expense)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $expense->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Expense $expense)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $expense->tenant_id === $tenant->id;
    }

    public function delete(User $user, Expense $expense)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $expense->tenant_id === $tenant->id;
    }
}
