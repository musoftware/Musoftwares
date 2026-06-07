<?php

namespace Modules\Booking\tests\Feature\Booking\Recurring;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;

class TenantIsolationRecurringTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_can_only_view_own_recurring_series()
    {
        $user1 = User::factory()->create([]);
        $user2 = User::factory()->create([]);

        $customer1 = User::factory()->create([]);
        $customer2 = User::factory()->create([]);

        RecurringSeries::create([
            'tenant_id' => $user1->id,
            'customer_id' => $customer1->id,
            'rrule' => 'FREQ=WEEKLY',
            'starts_at' => now(),
        ]);

        RecurringSeries::create([
            'tenant_id' => $user2->id,
            'customer_id' => $customer2->id,
            'rrule' => 'FREQ=WEEKLY',
            'starts_at' => now(),
        ]);

        $response1 = $this->actingAs($user1)->getJson('/api/v1/recurring-series');
        $response1->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals($user1->id, $response1->json()[0]['tenant_id']);

        $response2 = $this->actingAs($user2)->getJson('/api/v1/recurring-series');
        $response2->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals($user2->id, $response2->json()[0]['tenant_id']);
    }
}
