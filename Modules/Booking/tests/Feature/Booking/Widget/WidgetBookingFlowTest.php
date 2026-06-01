<?php

namespace Modules\Booking\tests\Feature\Booking\Widget;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetDomain;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetLog;
use Modules\Booking\app\Features\Widget\Events\WidgetBookingCreated;
use Modules\Booking\app\Features\Widget\Events\WidgetViewed;

class WidgetBookingFlowTest extends TestCase
{
    use DatabaseTransactions;

    public function test_public_user_can_view_and_book_via_widget()
    {
        Event::fake([WidgetViewed::class, WidgetBookingCreated::class]);

        $widget = BookingWidget::create(['tenant_id' => 1, 'name' => 'Test Widget']);
        BookingWidgetDomain::create(['tenant_id' => 1, 'widget_id' => $widget->id, 'domain' => 'clinic.com']);

        // 1. View Widget
        $response = $this->postJson("/api/public/widgets/{$widget->uuid}/view", [], [
            'Origin' => 'https://clinic.com'
        ]);
        
        $response->assertStatus(200);
        $this->assertDatabaseHas('booking_widget_logs', [
            'widget_id' => $widget->id,
            'action' => 'view'
        ]);
        Event::assertDispatched(WidgetViewed::class);

        // 2. Complete Booking
        $response = $this->postJson("/api/public/widgets/{$widget->uuid}/book", [], [
            'Origin' => 'https://clinic.com'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('booking_widget_logs', [
            'widget_id' => $widget->id,
            'action' => 'complete_booking'
        ]);
        Event::assertDispatched(WidgetBookingCreated::class);
    }
}
