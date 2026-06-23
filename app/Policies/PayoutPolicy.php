<?php

namespace App\Policies;

use App\Models\Payout;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayoutPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, Payout $payout)
    {
        return $user->hasAnyRole(['admin', 'super_admin']) || $payout->user_id === $user->id;
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, Payout $payout)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, Payout $payout)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }
}
