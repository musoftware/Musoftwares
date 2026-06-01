<?php

namespace Modules\Booking\tests\Feature\Booking\Widget;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetDomain;
use Modules\Booking\app\Features\Widget\Http\Middleware\ValidateWidgetDomain;
use Illuminate\Http\JsonResponse;

class WidgetDomainValidatorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_allows_request_from_whitelisted_domain()
    {
        $widget = BookingWidget::create(['tenant_id' => 1, 'name' => 'Test Widget']);
        BookingWidgetDomain::create(['tenant_id' => 1, 'widget_id' => $widget->id, 'domain' => 'myclinic.com']);

        $request = Request::create('/api/public/widgets/' . $widget->uuid . '/embed.js', 'GET');
        $request->headers->set('Origin', 'https://myclinic.com');

        $middleware = new ValidateWidgetDomain();
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_blocks_request_from_unauthorized_domain()
    {
        $widget = BookingWidget::create(['tenant_id' => 1, 'name' => 'Test Widget']);
        BookingWidgetDomain::create(['tenant_id' => 1, 'widget_id' => $widget->id, 'domain' => 'myclinic.com']);

        $request = Request::create('/api/public/widgets/' . $widget->uuid . '/embed.js', 'GET');
        $request->headers->set('Origin', 'https://hacker-site.com');

        $middleware = new ValidateWidgetDomain();
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('CORS Policy', $response->getContent());
    }
}
