<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\Service;

class ServicePolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('admin') || $user->hasRole('super_admin') || $user->hasRole('Admin') || !empty($user->is_admin) || ($user->role ?? null) === 'admin') {
            return true;
        }

        return null;
    }

    public function update(User $user, Service $service): bool
    {
        return $user->id === $service->seller_id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->id === $service->seller_id;
    }
}
