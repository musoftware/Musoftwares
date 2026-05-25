<?php

namespace Modules\Booking\tests\Feature\Booking\Widget;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetDomain;

class TenantIsolationWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_cannot_view_other_tenant_widgets()
    {
        $user1 = User::factory()->create(['tenant_id' => 1]);
        $user2 = User::factory()->create(['tenant_id' => 2]);

        BookingWidget::create(['tenant_id' => 1, 'name' => 'Tenant 1 Widget']);
        BookingWidget::create(['tenant_id' => 2, 'name' => 'Tenant 2 Widget']);

        $response = $this->actingAs($user1)->getJson('/api/widgets');
        
        $response->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals('Tenant 1 Widget', $response->json()[0]['name']);
    }

    public function test_tenant_creates_widget_automatically_scoped()
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        $response = $this->actingAs($user)->postJson('/api/widgets', [
            'name' => 'New Widget',
            'type' => 'popup',
            'domains' => ['myclinic.com']
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('booking_widgets', [
            'tenant_id' => 1,
            'name' => 'New Widget'
        ]);

        $this->assertDatabaseHas('booking_widget_domains', [
            'tenant_id' => 1,
            'domain' => 'myclinic.com'
        ]);
    }
}
