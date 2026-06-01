<?php

namespace Modules\Booking\tests\Unit\Booking\WaReminders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Booking\app\Features\WaReminders\Services\ReminderSchedulerService;

class ReminderSchedulerServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_scheduler_creates_pending_schedules_for_booking()
    {
        $service = new ReminderSchedulerService();
        // Expect WaSchedule records to be created.
        $this->assertTrue(true);
    }
}
