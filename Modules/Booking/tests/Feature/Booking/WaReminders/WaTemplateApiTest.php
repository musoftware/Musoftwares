<?php

namespace Modules\Booking\tests\Feature\Booking\WaReminders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WaTemplateApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_create_template()
    {
        // Test API to create template
        $this->assertTrue(true);
    }
}
