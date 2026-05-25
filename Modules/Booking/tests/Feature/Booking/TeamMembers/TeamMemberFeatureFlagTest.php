<?php

namespace Modules\Booking\tests\Feature\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class TeamMemberFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_without_addon_cannot_add_second_staff()
    {
        $tenantId = 1;
        $admin = User::factory()->create(['tenant_id' => $tenantId]);
        
        $this->actingAs($admin);
        
        // Mock the limits service directly or assume the controller will deny it
        // based on the underlying limits logic
        // Because mocking helpers in pest/phpunit without extensions is messy,
        // we assume `feature('booking-team-members')` returns false by default in tests.
        
        // Mock current count to be 1 so limits fail
        \Modules\Booking\app\Features\TeamMembers\Models\BookingTeamMember::create([
            'tenant_id' => $tenantId,
            'user_id' => $admin->id,
        ]);

        $response = $this->postJson('/api/v1/team-members', [
            'name' => 'John',
            'email' => 'j2@ex.com',
        ]);

        // Expect 403 Forbidden due to limit exceeded
        $response->assertStatus(403)
                 ->assertJsonFragment(['message' => 'Feature locked. Upgrade your subscription to add more team members.']);
    }
}
