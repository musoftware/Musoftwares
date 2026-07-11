<?php

namespace Tests\Feature\BookingRules;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingAdvancedRulesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_booking_rule()
    {
        $payload = [
            'name' => 'Reject large bookings',
            'event_trigger' => 'booking.created',
            'priority' => 10,
            'conditions' => [
                ['type' => 'amount', 'operator' => 'greater_than', 'value' => json_encode(['amount' => 5000])],
            ],
            'actions' => [
                ['type' => 'reject'],
            ],
        ];

        $user = User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/booking-rules', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Reject large bookings');

        $this->assertDatabaseHas('booking_advanced_rules', ['name' => 'Reject large bookings']);
        $this->assertDatabaseHas('booking_advanced_rule_conditions', ['type' => 'amount']);
        $this->assertDatabaseHas('booking_advanced_rule_actions', ['type' => 'reject']);
    }

    // Add more end to end flow tests...
}
