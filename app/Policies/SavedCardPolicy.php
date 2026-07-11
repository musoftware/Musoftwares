<?php

namespace App\Policies;

use App\Models\SavedCard;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class SavedCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, SavedCard $savedCard)
    {
        return $savedCard->user_id === $user->id;
    }

    public function create(User $user)
    {
        return true;
    }

    public function update(User $user, SavedCard $savedCard)
    {
        return $savedCard->user_id === $user->id;
    }

    public function delete(User $user, SavedCard $savedCard)
    {
        return $savedCard->user_id === $user->id;
    }
}
