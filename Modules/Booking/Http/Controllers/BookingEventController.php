<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Booking\Models\BookingEventType;
use Illuminate\Support\Facades\Auth;

class BookingEventController extends Controller
{
    public function index(Request $request)
    {
        $events = BookingEventType::where('user_id', Auth::id())->get();

        return Inertia::render('Booking/Index', [
            'events' => $events
        ]);
    }

    public function create()
    {
        return Inertia::render('Booking/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:booking_event_types,slug',
            'duration_minutes' => 'required|integer|min:5',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|string|size:3',
            'requires_payment' => 'boolean',
        ]);

        $event = BookingEventType::create([
            'user_id' => Auth::id(),
            ...$validated,
            'is_active' => true,
        ]);

        return redirect()->route('booking.events.index')->with('success', __('general.booking_event_created_successfully'));
    }

    public function edit($slug)
    {
        $event = BookingEventType::where('user_id', Auth::id())->where('slug', $slug)->firstOrFail();

        return Inertia::render('Booking/Edit', [
            'event' => $event
        ]);
    }

    public function update(Request $request, $slug)
    {
        $event = BookingEventType::where('user_id', Auth::id())->where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:5',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'requires_payment' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $event->update($validated);

        return redirect()->route('booking.events.index')->with('success', __('general.booking_event_updated'));
    }
}
