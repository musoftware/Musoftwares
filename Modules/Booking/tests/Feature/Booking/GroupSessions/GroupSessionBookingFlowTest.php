<?php

namespace Modules\Booking\tests\Feature\Booking\GroupSessions;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Illuminate\Support\Carbon;

class GroupSessionBookingFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_customer_can_join_group_session()
    {
        $user = User::factory()->create(['tenant_id' => 1]);
        
        $session = GroupSession::create([
            'tenant_id' => 1,
            'title' => 'CrossFit Bootcamp',
            'starts_at' => Carbon::tomorrow(),
            'ends_at' => Carbon::tomorrow()->addHour(),
            'max_capacity' => 20,
        ]);

        $response = $this->actingAs($user)->postJson("/api/group-sessions/{$session->id}/join", [
            'customer_id' => 50
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'confirmed');
                 
        $this->assertDatabaseHas('booking_group_session_participants', [
            'group_session_id' => $session->id,
            'customer_id' => 50,
            'status' => 'confirmed'
        ]);
    }
}
