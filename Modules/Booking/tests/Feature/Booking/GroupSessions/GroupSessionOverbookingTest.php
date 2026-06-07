<?php

namespace Modules\Booking\tests\Feature\Booking\GroupSessions;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Illuminate\Support\Carbon;

class GroupSessionOverbookingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_overbooking_routes_customer_to_waitlist()
    {
        $user = User::factory()->create([]);
        
        $session = GroupSession::create([
            'tenant_id' => $user->id,
            'title' => 'Waitlist Test Class',
            'starts_at' => Carbon::tomorrow(),
            'ends_at' => Carbon::tomorrow()->addHour(),
            'max_capacity' => 1, // Only 1 seat
        ]);

        // First customer secures the seat
        $this->actingAs($user)->postJson("/api/v1/group-sessions/{$session->id}/join", ['customer_id' => 10]);

        // Second customer tries, should hit waitlist
        $response = $this->actingAs($user)->postJson("/api/v1/group-sessions/{$session->id}/join", ['customer_id' => 11]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'waitlisted');
                 
        $this->assertDatabaseHas('booking_group_session_waitlists', [
            'group_session_id' => $session->id,
            'customer_id' => 11,
            'status' => 'waiting'
        ]);
    }
}
