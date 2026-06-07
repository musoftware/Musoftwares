<?php

namespace Modules\Booking\tests\Feature\Booking\Widget;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetDomain;
use Modules\Booking\app\Features\Widget\Http\Middleware\ValidateWidgetDomain;
use Illuminate\Http\JsonResponse;

class WidgetDomainValidatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_request_from_whitelisted_domain()
    {
        $widget = BookingWidget::create(['tenant_id' => 1, 'name' => 'Test Widget']);
        BookingWidgetDomain::create(['tenant_id' => 1, 'widget_id' => $widget->id, 'domain' => 'myclinic.com']);

        $response = $this->postJson('/api/public/widgets/' . $widget->uuid . '/view', [], [
            'Origin' => 'https://myclinic.com'
        ]);

        $response->assertStatus(200);
    }

    public function test_blocks_request_from_unauthorized_domain()
    {
        $widget = BookingWidget::create(['tenant_id' => 1, 'name' => 'Test Widget']);
        BookingWidgetDomain::create(['tenant_id' => 1, 'widget_id' => $widget->id, 'domain' => 'myclinic.com']);

        $response = $this->postJson('/api/public/widgets/' . $widget->uuid . '/view', [], [
            'Origin' => 'https://hacker-site.com'
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'CORS Policy: Domain hacker-site.com is not whitelisted for this widget.']);
    }
}
