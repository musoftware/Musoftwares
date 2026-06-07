<?php

namespace Modules\Booking\tests\Feature\Booking\Widget;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetDomain;

class TenantIsolationWidgetTest extends TestCase
{
    use DatabaseTransactions;

    public function test_tenant_cannot_view_other_tenant_widgets()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        BookingWidget::create(['tenant_id' => $user1->id, 'name' => 'Tenant 1 Widget']);
        BookingWidget::create(['tenant_id' => $user2->id, 'name' => 'Tenant 2 Widget']);

        $response = $this->actingAs($user1)->getJson('/api/v1/widgets');
        
        $response->assertStatus(200)->assertJsonCount(1);
        $this->assertEquals('Tenant 1 Widget', $response->json()[0]['name']);
    }

    public function test_tenant_creates_widget_automatically_scoped()
    {
        $user = User::factory()->create();

        $this->mock(\Modules\Booking\app\Features\Widget\Services\BookingWidgetLimitsService::class, function ($mock) {
            $mock->shouldReceive('canUseWidget')->andReturn(true);
        });

        $response = $this->actingAs($user)->postJson('/api/v1/widgets', [
            'name' => 'New Widget',
            'type' => 'popup',
            'domains' => ['myclinic.com']
        ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('booking_widgets', [
            'tenant_id' => $user->id,
            'name' => 'New Widget'
        ]);

        $this->assertDatabaseHas('booking_widget_domains', [
            'tenant_id' => $user->id,
            'domain' => 'myclinic.com'
        ]);
    }
}
