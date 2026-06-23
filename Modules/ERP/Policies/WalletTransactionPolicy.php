<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\ERP\Models\WalletTransaction;
use Illuminate\Foundation\Auth\User;

class WalletTransactionPolicy
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

    public function view(User $user, WalletTransaction $walletTransaction)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $walletTransaction->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, WalletTransaction $walletTransaction)
    {
        return false; // Transactions should generally be immutable
    }

    public function delete(User $user, WalletTransaction $walletTransaction)
    {
        return false; // Transactions should not be deleted
    }
}
