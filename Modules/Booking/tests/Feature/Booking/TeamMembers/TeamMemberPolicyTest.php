<?php

namespace Modules\Booking\tests\Feature\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;

class TeamMemberPolicyTest extends TestCase
{
    use DatabaseTransactions;

    public function test_staff_cannot_edit_other_staff_profiles()
    {
        $staff1 = User::factory()->create([]);
        $tenantId = $staff1->id;
        $profile1 = BookingTeamMember::create(['tenant_id' => $tenantId, 'user_id' => $staff1->id]);

        $staff2 = User::factory()->create([]);
        $profile2 = BookingTeamMember::create(['tenant_id' => $tenantId, 'user_id' => $staff2->id]);

        // Staff 1 tries to edit Staff 2
        $this->actingAs($staff1);

        $response = $this->putJson("/api/v1/team-members/{$profile2->id}", [
            'bio' => 'Hacked Bio',
        ]);

        $response->assertStatus(403);
    }
}
