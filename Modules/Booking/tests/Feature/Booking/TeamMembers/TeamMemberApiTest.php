<?php

namespace Modules\Booking\tests\Feature\Booking\TeamMembers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class TeamMemberApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_validation_requires_name_and_email()
    {
        $admin = User::factory()->create(['tenant_id' => 1]);
        $this->actingAs($admin);

        $response = $this->postJson('/api/v1/team-members', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email']);
    }
}
