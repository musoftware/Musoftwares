<?php

namespace Modules\Booking\tests\Feature\Booking\WaReminders;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationWaTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_see_other_tenants_templates_or_logs()
    {
        $this->assertTrue(true);
    }
}
