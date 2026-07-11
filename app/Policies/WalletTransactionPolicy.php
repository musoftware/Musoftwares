<?php

namespace App\Policies;

use App\Models\WalletTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class WalletTransactionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
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
