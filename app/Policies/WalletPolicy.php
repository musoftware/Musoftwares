<?php

namespace App\Policies;

use App\Models\Wallet;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class WalletPolicy
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

    public function view(User $user, Wallet $wallet)
    {
        return $wallet->user_id === $user->id || $user->hasRole('super_admin');
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, Wallet $wallet)
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, Wallet $wallet)
    {
        return true;
    }
}
