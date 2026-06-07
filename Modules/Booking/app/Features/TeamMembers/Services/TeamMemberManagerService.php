<?php

namespace Modules\Booking\app\Features\TeamMembers\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamMemberManagerService
{
    /**
     * Creates a system user and links them to a booking profile in one transaction.
     */
    public function createTeamMember(array $data)
    {
        return DB::transaction(function () use ($data) {
            $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()) ?? $data['tenant_id'];

            // Create System User
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(Str::random(16)), // Secure random password
                'tenant_id' => $tenantId,
            ]);

            // Create Booking Profile
            $profile = BookingTeamMember::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'job_title' => $data['job_title'] ?? null,
                'bio' => $data['bio'] ?? null,
                'is_bookable' => $data['is_bookable'] ?? true,
                'is_active' => true,
            ]);

            return $profile;
        });
    }
}
