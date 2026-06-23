<?php

namespace App\Policies;

use App\Models\WalletTransaction;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletTransactionPolicy
{

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['super_admin', 'admin', 'superadmin', 'Admin'])) {
            return true;
        }
        return null;
    }

    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, WalletTransaction $transaction)
    {
        return $transaction->wallet->user_id === $user->id || $user->hasRole('super_admin');
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, WalletTransaction $transaction)
    {
        return true;
    }

    public function delete(User $user, WalletTransaction $transaction)
    {
        return isset($transaction->user_id) ? $user->id === $transaction->user_id : false;
    }
}
