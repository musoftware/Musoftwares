<?php

namespace Modules\ERP\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User;
use Modules\ERP\Models\Product;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class ProductPolicy
{
    use HandlesAuthorization;

    protected function getTenant(User $user)
    {
        $tenantUser = $user;
        if (Auth::guard('erp_team')->check()) {
            $tenantUser = Auth::guard('erp_team')->user()->tenant->user ?? $user;
        }

        if (!$tenantUser || !$tenantUser->hasModuleSubscription('erp-inventory')) {
            return null;
        }

        $tenant = Tenant::where('user_id', $tenantUser->id)->first();
        if (!$tenant && Auth::guard('erp_team')->check()) {
            $tenant = Auth::guard('erp_team')->user()->tenant;
        }
        
        return $tenant;
    }

    public function viewAny(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function view(User $user, Product $product)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $product->tenant_id === $tenant->id;
    }

    public function create(User $user)
    {
        return $this->getTenant($user) !== null;
    }

    public function update(User $user, Product $product)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $product->tenant_id === $tenant->id;
    }

    public function delete(User $user, Product $product)
    {
        $tenant = $this->getTenant($user);
        return $tenant && $product->tenant_id === $tenant->id;
    }
}
