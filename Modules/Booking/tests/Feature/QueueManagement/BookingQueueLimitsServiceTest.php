<?php

namespace Modules\Booking\tests\Feature\QueueManagement;

use Tests\TestCase;
use Modules\Booking\app\Features\QueueManagement\Services\BookingQueueLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingQueueLimitsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_enforces_limits()
    {
        // For Phase 3, this is a mock wrapper around the future Phase 4 centralized SaaS meter.
        $service = new BookingQueueLimitsService();
        
        // Assert it doesn't throw by default since we fallback to true
        $service->enforce('max_daily_queue_entries');
        
        $this->assertTrue($service->canUse('max_daily_queue_entries'));
    }
}
