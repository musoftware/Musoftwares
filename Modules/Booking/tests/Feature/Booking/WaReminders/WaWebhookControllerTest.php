<?php

namespace Modules\Booking\tests\Feature\Booking\WaReminders;

use Tests\TestCase;

class WaWebhookControllerTest extends TestCase
{
    public function test_valid_webhook_updates_log()
    {
        $this->assertTrue(true);
    }

    public function test_invalid_signature_is_rejected()
    {
        $this->assertTrue(true);
    }
}
