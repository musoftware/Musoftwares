<?php

namespace Modules\Booking\app\Features\Reminders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\Reminders\Models\BookingWaTemplate;
use Modules\Booking\app\Features\Reminders\Http\Requests\StoreWaTemplateRequest;

class WaTemplateController extends Controller
{
    public function __construct()
    {
        // Require feature flag to access any template endpoints
        $this->middleware(function ($request, $next) {
            if (!feature('booking-wa-reminders')) {
                return response()->json(['message' => 'Feature locked. Upgrade to enable WhatsApp reminders.'], 403);
            }
            return $next($request);
        });
    }

    public function index()
    {
        // Global scope handles tenant_id
        $templates = BookingWaTemplate::latest()->get();
        return response()->json($templates);
    }

    public function store(StoreWaTemplateRequest $request)
    {
        $template = BookingWaTemplate::create($request->validated());
        return response()->json($template, 201);
    }

    public function show(BookingWaTemplate $template)
    {
        return response()->json($template);
    }

    public function update(StoreWaTemplateRequest $request, BookingWaTemplate $template)
    {
        $template->update($request->validated());
        return response()->json($template);
    }

    public function destroy(BookingWaTemplate $template)
    {
        $template->delete();
        return response()->json(null, 204);
    }
}
