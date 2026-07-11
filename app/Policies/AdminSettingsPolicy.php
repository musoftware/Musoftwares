<?php

namespace App\Policies;

use App\Models\AdminSettings;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;

class AdminSettingsPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function view(User $user, AdminSettings $adminSettings)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function create(User $user)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function update(User $user, AdminSettings $adminSettings)
    {
        return $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $user, AdminSettings $adminSettings)
    {
        return clone $user->hasAnyRole(['admin', 'super_admin']);
    }
}
