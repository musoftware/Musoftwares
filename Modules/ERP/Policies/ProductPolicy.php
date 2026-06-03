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
        $tenant = $user->tenant;
        if (!$tenant || !$user->hasModuleSubscription('erp-inventory')) {
            return null;
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
