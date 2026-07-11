<?php

namespace App\Policies;

use App\Models\Billing\PlatformContract;
use App\Models\User;

class PlatformContractPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PlatformContract $platformContract): bool
    {
        return isset($platformContract->user_id) ? $user->id === $platformContract->user_id : false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlatformContract $platformContract): bool
    {
        return isset($platformContract->user_id) ? $user->id === $platformContract->user_id : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlatformContract $platformContract): bool
    {
        return isset($platformContract->user_id) ? $user->id === $platformContract->user_id : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlatformContract $platformContract): bool
    {
        return isset($platformContract->user_id) ? $user->id === $platformContract->user_id : false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlatformContract $platformContract): bool
    {
        return isset($platformContract->user_id) ? $user->id === $platformContract->user_id : false;
    }
}
