<?php

namespace Modules\Booking\tests\Feature\Booking\GroupSessions;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Booking\app\Features\GroupSessions\Models\GroupSession;
use Modules\Booking\app\Features\GroupSessions\Models\GroupParticipant;
use Modules\Booking\app\Features\GroupSessions\Models\GroupWaitlist;
use Modules\Booking\app\Features\GroupSessions\Jobs\PromoteWaitlistJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

class PromoteWaitlistJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_promotes_next_waitlist_user_when_job_runs()
    {
        Event::fake();

        $session = GroupSession::create([
            'tenant_id' => 1,
            'title' => 'Yoga Class',
            'starts_at' => Carbon::tomorrow(),
            'ends_at' => Carbon::tomorrow()->addHour(),
            'max_capacity' => 1,
        ]);

        // Waitlist user
        GroupWaitlist::create([
            'tenant_id' => 1,
            'group_session_id' => $session->id,
            'customer_id' => 99,
            'status' => 'waiting'
        ]);

        $job = new PromoteWaitlistJob($session->id);
        $job->handle(
            app(\Modules\Booking\app\Features\GroupSessions\Services\WaitlistManager::class),
            app(\Modules\Booking\app\Features\GroupSessions\Services\GroupCapacityManager::class)
        );

        $this->assertDatabaseHas('booking_group_session_waitlists', [
            'group_session_id' => $session->id,
            'customer_id' => 99,
            'status' => 'promoted'
        ]);

        $this->assertDatabaseHas('booking_group_session_participants', [
            'group_session_id' => $session->id,
            'customer_id' => 99,
        ]);
    }
}
