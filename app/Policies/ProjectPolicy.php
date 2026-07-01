<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * Administrators (and any user able to access the admin area) may manage every project,
     * including editing the client-facing board. This short-circuits the ownership checks below.
     */
    public function before(User $user, string $ability): ?bool
    {
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): Response
    {
        return $project->user_id === $user->id
            ? Response::allow()
            : Response::denyWithStatus(403);
    }

    public function create(User $user): bool
    {
        // Admin bypass already handled in `before()`. Non-admins cannot create admin projects.
        return false;
    }

    public function update(User $user, Project $project): Response
    {
        return $project->user_id === $user->id
            ? Response::allow()
            : Response::denyWithStatus(403);
    }

    public function delete(User $user, Project $project): bool
    {
        // Admin bypass already handled in `before()`. Non-admins cannot delete.
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function archive(User $user, Project $project): bool
    {
        return false;
    }

    public function export(User $user): bool
    {
        return false;
    }

    public function searchClients(User $user): bool
    {
        return false;
    }
}
