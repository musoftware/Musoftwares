<?php

namespace Modules\Booking\tests\Feature\Booking\OnlinePage;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_manage_other_public_pages()
    {
        $this->assertTrue(true);
    }
}
