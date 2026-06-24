<?php

namespace App\Policies;

use App\Models\ContractPriceItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPriceItemPolicy
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
    public function view(User $user, ContractPriceItem $contractPriceItem): bool
    {
        return isset($contractPriceItem->user_id) ? $user->id === $contractPriceItem->user_id : false;
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
    public function update(User $user, ContractPriceItem $contractPriceItem): bool
    {
        return isset($contractPriceItem->user_id) ? $user->id === $contractPriceItem->user_id : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContractPriceItem $contractPriceItem): bool
    {
        return isset($contractPriceItem->user_id) ? $user->id === $contractPriceItem->user_id : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ContractPriceItem $contractPriceItem): bool
    {
        return isset($contractPriceItem->user_id) ? $user->id === $contractPriceItem->user_id : false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ContractPriceItem $contractPriceItem): bool
    {
        return isset($contractPriceItem->user_id) ? $user->id === $contractPriceItem->user_id : false;
    }
}
