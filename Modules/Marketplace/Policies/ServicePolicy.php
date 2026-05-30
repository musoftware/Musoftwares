<?php

namespace Modules\Marketplace\Policies;

use App\Models\User;
use Modules\Marketplace\Models\Service;

class ServicePolicy
{
    public function update(User $user, Service $service): bool
    {
        return $user->id === $service->seller_id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->id === $service->seller_id;
    }
}
