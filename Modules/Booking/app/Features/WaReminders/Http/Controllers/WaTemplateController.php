<?php

namespace Modules\Booking\app\Features\WaReminders\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\app\Features\WaReminders\Models\WaTemplate;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class WaTemplateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!feature('booking.wa_reminders')) {
                    return response()->json(['message' => 'Feature locked. Upgrade to unlock WhatsApp reminders.'], 403);
                }
                return $next($request);
            }),
        ];
    }

    public function index()
    {
        // Simple view, no advanced policy needed if tenant scoped globally
        return response()->json(WaTemplate::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = WaTemplate::updateOrCreate(
            ['tenant_id' => (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()), 'type' => $validated['type']],
            $validated
        );

        return response()->json($template, 201);
    }
}
