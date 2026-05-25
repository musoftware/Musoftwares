<?php

namespace Modules\Booking\app\Features\MultiBranch\Policies;

use App\Models\User;
use Modules\Booking\app\Features\MultiBranch\Models\BookingBranch;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingBranchPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return feature('booking-multi-branch', $user->tenant_id);
    }

    public function view(User $user, BookingBranch $branch)
    {
        // Must belong to the same tenant
        if ($user->tenant_id !== $branch->tenant_id) {
            return false;
        }

        // Example: Only branch managers or admins can view branch details
        // In reality, this links with your spatie/laravel-permission logic
        return true; 
    }

    public function create(User $user)
    {
        return feature('booking-multi-branch', $user->tenant_id);
    }

    public function update(User $user, BookingBranch $branch)
    {
        return $user->tenant_id === $branch->tenant_id;
    }

    public function delete(User $user, BookingBranch $branch)
    {
        return $user->tenant_id === $branch->tenant_id;
    }

    public function assignStaff(User $user, BookingBranch $branch)
    {
        return $user->tenant_id === $branch->tenant_id;
    }
}
