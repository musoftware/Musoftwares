<?php

namespace App\Policies;

use App\Models\KanbanTask;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KanbanTaskPolicy
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
    public function view(User $user, KanbanTask $kanbanTask): bool
    {
        return isset($kanbanTask->user_id) ? $user->id === $kanbanTask->user_id : false;
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
    public function update(User $user, KanbanTask $kanbanTask): bool
    {
        return isset($kanbanTask->user_id) ? $user->id === $kanbanTask->user_id : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KanbanTask $kanbanTask): bool
    {
        return isset($kanbanTask->user_id) ? $user->id === $kanbanTask->user_id : false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KanbanTask $kanbanTask): bool
    {
        return isset($kanbanTask->user_id) ? $user->id === $kanbanTask->user_id : false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KanbanTask $kanbanTask): bool
    {
        return isset($kanbanTask->user_id) ? $user->id === $kanbanTask->user_id : false;
    }
}
