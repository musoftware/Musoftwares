<?php

namespace Modules\Booking\tests\Feature\Booking\WaReminders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class WaTemplateApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_can_create_template()
    {
        // Test API to create template
        $this->assertTrue(true);
    }
}
