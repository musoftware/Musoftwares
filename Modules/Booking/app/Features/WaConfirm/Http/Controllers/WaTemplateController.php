<?php

namespace Modules\Booking\app\Features\WaConfirm\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaTemplate;

class WaTemplateController extends Controller
{
    public function index()
    {
        if (!feature('booking.wa_confirm')) {
            abort(403, 'Unlock WhatsApp Confirmations to manage templates.');
        }

        $templates = BookingWaTemplate::all();

        return response()->json($templates);
    }

    public function store(Request $request)
    {
        if (!feature('booking.wa_confirm')) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'required|string',
            'is_active' => 'boolean',
        ]);

        $template = BookingWaTemplate::create($validated);

        return response()->json($template, 201);
    }
}
