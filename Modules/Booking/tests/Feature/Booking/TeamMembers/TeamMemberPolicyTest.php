<?php

namespace Modules\Booking\tests\Feature\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;

class TeamMemberPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_cannot_edit_other_staff_profiles()
    {
        $tenantId = 1;
        $staff1 = User::factory()->create(['tenant_id' => $tenantId]);
        $profile1 = BookingTeamMember::create(['tenant_id' => $tenantId, 'user_id' => $staff1->id]);

        $staff2 = User::factory()->create(['tenant_id' => $tenantId]);
        $profile2 = BookingTeamMember::create(['tenant_id' => $tenantId, 'user_id' => $staff2->id]);

        // Staff 1 tries to edit Staff 2
        $this->actingAs($staff1);

        $response = $this->putJson("/api/v1/team-members/{$profile2->id}", [
            'bio' => 'Hacked Bio',
        ]);

        $response->assertStatus(403);
    }
}
