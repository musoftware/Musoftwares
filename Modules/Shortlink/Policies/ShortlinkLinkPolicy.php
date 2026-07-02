<?php

namespace Modules\Shortlink\Policies;

use App\Models\User;
use Modules\Shortlink\Models\ShortlinkLink;

class ShortlinkLinkPolicy
{
    /**
     * Administrators may manage every short link. Everyone else is denied
     * (the route middleware already gates on the admin middleware; this is
     * the application-layer defense-in-depth).
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
        return false;
    }

    public function view(User $user, ShortlinkLink $link): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ShortlinkLink $link): bool
    {
        return false;
    }

    public function delete(User $user, ShortlinkLink $link): bool
    {
        return false;
    }
}
