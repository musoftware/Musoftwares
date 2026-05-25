<?php

namespace Modules\Booking\tests\Feature\Booking\Recurring;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;

class TenantIsolationRecurringTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_only_view_own_recurring_series()
    {
        $user1 = User::factory()->create(['tenant_id' => 1]);
        $user2 = User::factory()->create(['tenant_id' => 2]);

        RecurringSeries::create([
            'tenant_id' => 1,
            'customer_id' => 10,
            'rrule' => 'FREQ=WEEKLY',
            'starts_at' => now(),
        ]);

        RecurringSeries::create([
            'tenant_id' => 2,
            'customer_id' => 20,
            'rrule' => 'FREQ=WEEKLY',
            'starts_at' => now(),
        ]);

        $response1 = $this->actingAs($user1)->getJson('/api/recurring-series');
        $response1->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals(1, $response1->json()[0]['tenant_id']);

        $response2 = $this->actingAs($user2)->getJson('/api/recurring-series');
        $response2->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals(2, $response2->json()[0]['tenant_id']);
    }
}
