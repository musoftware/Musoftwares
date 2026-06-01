<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantClientPolicy
{
    use HandlesAuthorization;

    protected function getTenant(User $user)
    {
        $tenant = Tenant::where('user_id', $user->id)->first();
        if (!$tenant && Auth::guard('erp_team')->check()) {
            $tenant = Auth::guard('erp_team')->user()->tenant;
        }
        return $tenant;
    }

    public function viewAny(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function view(User $user, TenantClient $client)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $client->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        if ($user instanceof \Modules\ERP\Models\TeamMember && !$user->isManager()) {
            return false;
        }
        return $this->getTenant($user) !== null;
    }


    public function update(User $user, TenantClient $client)
    {
        if ($user instanceof \Modules\ERP\Models\TeamMember && !$user->isManager()) {
            return false;
        }
        $tenant = $this->getTenant($user);
        return $tenant && $client->tenant_id === $tenant->id;
    }

    public function delete(User $user, TenantClient $client)
    {
        if ($user instanceof \Modules\ERP\Models\TeamMember && !$user->isManager()) {
            return false;
        }
        $tenant = $this->getTenant($user);
        return $tenant && $client->tenant_id === $tenant->id;
    }
}
