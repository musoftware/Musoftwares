<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WalletTransfer;
use Illuminate\Auth\Access\Response;

class WalletTransferPolicy
{

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(['super_admin', 'admin', 'superadmin', 'Admin'])) {
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
    public function view(User $user, WalletTransfer $walletTransfer): bool
    {
        return isset($walletTransfer->user_id) ? $user->id === $walletTransfer->user_id : false;
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
    public function update(User $user, WalletTransfer $walletTransfer): bool
    {
        return isset($walletTransfer->user_id) ? $user->id === $walletTransfer->user_id : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, WalletTransfer $walletTransfer): bool
    {
        return isset($walletTransfer->user_id) ? $user->id === $walletTransfer->user_id : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, WalletTransfer $walletTransfer): bool
    {
        return isset($walletTransfer->user_id) ? $user->id === $walletTransfer->user_id : false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, WalletTransfer $walletTransfer): bool
    {
        return isset($walletTransfer->user_id) ? $user->id === $walletTransfer->user_id : false;
    }
}
