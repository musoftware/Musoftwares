<?php

namespace Modules\Booking\app\Features\Widget\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Widget\Models\BookingWidget;
use Modules\Booking\app\Features\Widget\Services\BookingWidgetService;
use Modules\Booking\app\Features\Widget\Services\BookingWidgetLimitsService;

class BookingWidgetController extends Controller
{
    protected $widgetService;
    protected $limitsService;

    public function __construct(BookingWidgetService $widgetService, BookingWidgetLimitsService $limitsService)
    {
        $this->widgetService = $widgetService;
        $this->limitsService = $limitsService;
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $widgets = BookingWidget::with('domains')->where('tenant_id', auth()->user()->tenant_id)->get();
        return response()->json($widgets);
    }

    public function store(Request $request)
    {
        if (!$this->limitsService->canUseWidget(auth()->user()->tenant_id)) {
            return response()->json(['message' => 'Feature locked. Upgrade to use Booking Widgets.'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'nullable|string|in:inline,popup',
            'primary_color' => 'nullable|string',
            'button_text' => 'nullable|string',
            'domains' => 'nullable|array',
            'domains.*' => 'string'
        ]);

        $widget = $this->widgetService->createWidget(auth()->user()->tenant_id, $validated);

        return response()->json($widget->load('domains'), 201);
    }
}
