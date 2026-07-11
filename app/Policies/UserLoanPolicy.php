<?php

namespace App\Policies;

use App\Models\UserLoan;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class UserLoanPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user)
    {
        return false;
    }

    public function view(User $user, UserLoan $userLoan)
    {
        return isset($userLoan->user_id) && $user->id === $userLoan->user_id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, UserLoan $userLoan)
    {
        return isset($userLoan->user_id) && $user->id === $userLoan->user_id;
    }

    public function delete(User $user, UserLoan $userLoan)
    {
        return isset($userLoan->user_id) && $user->id === $userLoan->user_id;
    }
}
