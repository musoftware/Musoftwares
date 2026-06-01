<?php

namespace Modules\Booking\tests\Unit\Booking\GroupSessions;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Modules\Booking\app\Features\GroupSessions\Services\GroupCapacityManager;
use Illuminate\Support\Carbon;

class GroupCapacityManagerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_prevents_securing_seat_if_at_capacity()
    {
        $session = GroupSession::create([
            'tenant_id' => 1,
            'title' => 'Yoga Class',
            'starts_at' => Carbon::tomorrow(),
            'ends_at' => Carbon::tomorrow()->addHour(),
            'max_capacity' => 1,
        ]);

        $manager = new GroupCapacityManager();
        
        // Secure first seat
        $result1 = $manager->secureSeat($session->id, 100);
        $this->assertTrue($result1['success']);

        // Secure second seat should fail
        $result2 = $manager->secureSeat($session->id, 101);
        $this->assertFalse($result2['success']);
        $this->assertEquals('full', $result2['reason']);
    }
}
