<?php

namespace App\Policies;

use App\Models\Coupon;
use Illuminate\Foundation\Auth\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CouponPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Coupon $coupon)
    {
        // Add specific user checks if coupon is restricted, but generally they might be viewable if public
        // or check if the user belongs to the tenant.
        return true;
    }

    public function create(User $user)
    {
        return $user->isAdmin();
    }

    public function update(User $user, Coupon $coupon)
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Coupon $coupon)
    {
        return $user->isAdmin();
    }
}
