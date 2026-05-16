<?php

namespace Modules\Core\Policies;

use App\Models\User;
use Modules\Core\Models\AdminNote;
use Illuminate\Auth\Access\HandlesAuthorization;

class AdminNotePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasRole(['super_admin', 'admin', 'moderator', 'support_agent', 'accountant']);
    }

    public function view(User $user, AdminNote $adminNote)
    {
        if ($adminNote->visibility === 'private') {
            return $user->id === $adminNote->author_id;
        }

        if ($adminNote->visibility === 'admins_only') {
            return $user->hasRole(['super_admin', 'admin']);
        }

        // staff_only
        return $user->hasRole(['super_admin', 'admin', 'moderator', 'support_agent', 'accountant']);
    }

    public function create(User $user)
    {
        return $user->hasRole(['super_admin', 'admin', 'moderator', 'support_agent', 'accountant']);
    }

    public function update(User $user, AdminNote $adminNote)
    {
        return $user->id === $adminNote->author_id || $user->hasRole('super_admin');
    }

    public function delete(User $user, AdminNote $adminNote)
    {
        return $user->id === $adminNote->author_id || $user->hasRole('super_admin');
    }
}
