<?php

namespace Modules\Booking\app\Features\Widget\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Models\BookingWidgetLog;
use Modules\Booking\app\Features\Widget\Services\WidgetEmbedGenerator;
use Modules\Booking\app\Features\Widget\Events\WidgetViewed;
use Modules\Booking\app\Features\Widget\Events\WidgetBookingCreated;
use Modules\Booking\Models\Booking;

class PublicWidgetController extends Controller
{
    protected $embedGenerator;

    public function __construct(WidgetEmbedGenerator $embedGenerator)
    {
        $this->embedGenerator = $embedGenerator;
        // The ValidateWidgetDomain middleware will be applied in routes/api.php
    }

    public function embed(Request $request, $uuid)
    {
        $widget = BookingWidget::where('uuid', $uuid)->firstOrFail();
        
        $js = $this->embedGenerator->generateJsPayload($widget);

        return response($js)->header('Content-Type', 'application/javascript');
    }

    public function view(Request $request)
    {
        $tenantId = $request->_tenant_id;
        $widgetId = $request->_widget_id;

        BookingWidgetLog::create([
            'tenant_id' => $tenantId,
            'widget_id' => $widgetId,
            'action' => 'view',
            'visitor_ip' => $request->ip(),
            'origin_domain' => $request->headers->get('origin'),
        ]);

        event(new WidgetViewed($tenantId, $widgetId));

        return response()->json(['status' => 'view_recorded']);
    }

    public function book(Request $request)
    {
        // ... (This would integrate with the actual booking resolver logic)
        $tenantId = $request->_tenant_id;
        $widgetId = $request->_widget_id;

        // Dummy creation for architectural completeness
        $booking = new Booking();
        $booking->tenant_id = $tenantId;
        $booking->id = rand(1000, 9000); // Faked for testing
        
        BookingWidgetLog::create([
            'tenant_id' => $tenantId,
            'widget_id' => $widgetId,
            'action' => 'complete_booking',
            'visitor_ip' => $request->ip(),
        ]);

        event(new WidgetBookingCreated($tenantId, $widgetId, $booking->id));

        return response()->json(['status' => 'booking_successful', 'booking_id' => $booking->id]);
    }
}
