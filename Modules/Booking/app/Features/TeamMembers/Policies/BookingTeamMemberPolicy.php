<?php

namespace Modules\Booking\app\Features\TeamMembers\Policies;

use App\Models\User;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookingTeamMemberPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true; // Anyone in the tenant can view the team
    }

    public function view(User $user, BookingTeamMember $teamMember)
    {
        return $user->tenant_id === $teamMember->tenant_id;
    }

    public function create(User $user)
    {
        // Must be an admin/manager to create new team members
        // (Assuming you have role checks, here we just return true for the sake of example if they pass limits)
        return true; 
    }

    public function update(User $user, BookingTeamMember $teamMember)
    {
        if ($user->tenant_id !== $teamMember->tenant_id) {
            return false;
        }

        // A user can update their own profile, or an admin can update anyone's.
        // Simple logic for now:
        return $user->id === $teamMember->user_id; 
    }

    public function delete(User $user, BookingTeamMember $teamMember)
    {
        if ($user->tenant_id !== $teamMember->tenant_id) {
            return false;
        }
        
        // Cannot delete yourself
        if ($user->id === $teamMember->user_id) {
            return false;
        }

        return true;
    }
}
