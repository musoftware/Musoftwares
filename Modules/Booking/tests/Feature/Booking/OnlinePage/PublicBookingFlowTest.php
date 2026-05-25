<?php

namespace Modules\Booking\tests\Feature\Booking\OnlinePage;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Booking\app\Features\OnlinePage\Models\PublicPage;

class PublicBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_initiate_booking_page()
    {
        $tenantId = 1;
        $page = PublicPage::create([
            'tenant_id' => $tenantId,
            'slug' => 'test-clinic',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/public/test-clinic/init');

        $response->assertStatus(200)
                 ->assertJsonPath('page.slug', 'test-clinic');
    }
}
