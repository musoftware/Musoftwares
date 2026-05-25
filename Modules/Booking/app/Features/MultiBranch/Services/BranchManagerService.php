<?php

namespace Modules\Booking\app\Features\MultiBranch\Services;

use Modules\Booking\app\Features\MultiBranch\Models\BookingBranch;
use Modules\Booking\app\Features\MultiBranch\Events\BranchStaffAssigned;

class BranchManagerService
{
    /**
     * Assign a user to a branch with a specific role.
     */
    public function assignStaff(BookingBranch $branch, int $userId, string $role = 'staff')
    {
        $tenantId = auth()->user()->tenant_id;

        // Using syncWithoutDetaching to avoid duplicating existing users
        $branch->users()->syncWithoutDetaching([
            $userId => ['role' => $role, 'tenant_id' => $tenantId]
        ]);

        $user = \App\Models\User::find($userId);

        if ($user) {
            event(new BranchStaffAssigned($branch, $user, $role));
        }

        return true;
    }

    /**
     * Remove a user from a branch.
     */
    public function removeStaff(BookingBranch $branch, int $userId)
    {
        return $branch->users()->detach($userId);
    }

    /**
     * Check if a user belongs to a specific branch.
     */
    public function isUserInBranch(BookingBranch $branch, int $userId): bool
    {
        return $branch->users()->where('user_id', $userId)->exists();
    }
}
