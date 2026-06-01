<?php

namespace Modules\Booking\tests\Feature\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember;

class TeamMemberIsolationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_cannot_see_other_tenants_team_members()
    {
        // Create Tenant A and User A
        $userA = User::factory()->create(['tenant_id' => 1]);
        $teamMemberA = BookingTeamMember::create([
            'tenant_id' => 1,
            'user_id' => $userA->id,
            'job_title' => 'Tenant A Staff',
        ]);

        // Create Tenant B and User B
        $userB = User::factory()->create(['tenant_id' => 2]);
        BookingTeamMember::create([
            'tenant_id' => 2,
            'user_id' => $userB->id,
            'job_title' => 'Tenant B Staff',
        ]);

        // Act as User A
        $this->actingAs($userA);

        // Fetch team members via API
        $response = $this->getJson('/api/v1/team-members');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Assert only Tenant A's staff is returned
        $this->assertCount(1, $data);
        $this->assertEquals('Tenant A Staff', $data[0]['job_title']);
    }

    public function test_tenant_cannot_edit_other_tenants_team_members()
    {
        $userA = User::factory()->create(['tenant_id' => 1]);
        
        $userB = User::factory()->create(['tenant_id' => 2]);
        $teamMemberB = BookingTeamMember::create([
            'tenant_id' => 2,
            'user_id' => $userB->id,
            'job_title' => 'Tenant B Staff',
        ]);

        // Act as User A and try to update User B's profile
        $this->actingAs($userA);

        $response = $this->putJson("/api/v1/team-members/{$teamMemberB->id}", [
            'job_title' => 'Hacked Title',
        ]);

        // Assert forbidden or not found due to global scope / policy
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
