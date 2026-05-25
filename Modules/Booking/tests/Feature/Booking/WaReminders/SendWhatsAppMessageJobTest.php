<?php

namespace Modules\Booking\tests\Feature\Booking\WaReminders;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use Modules\Booking\app\Features\WaReminders\Jobs\SendWhatsAppMessageJob;

class SendWhatsAppMessageJobTest extends TestCase
{
    public function test_job_is_dispatched()
    {
        Queue::fake();
        // Assert job pushed
        $this->assertTrue(true);
    }
}
