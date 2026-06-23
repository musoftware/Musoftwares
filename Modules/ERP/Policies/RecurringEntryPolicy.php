<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\RecurringEntry;
use Illuminate\Foundation\Auth\User;

class RecurringEntryPolicy
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

    public function view(User $user, RecurringEntry $recurringEntry)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $recurringEntry->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, RecurringEntry $recurringEntry)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $recurringEntry->tenant_id === $tenant->id;
    }

    public function delete(User $user, RecurringEntry $recurringEntry)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $recurringEntry->tenant_id === $tenant->id;
    }
}
