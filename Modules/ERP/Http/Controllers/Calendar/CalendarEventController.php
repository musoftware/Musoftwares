<?php

namespace Modules\ERP\Http\Controllers\Calendar;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Calendar\CalendarEvent;

class CalendarEventController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $events = CalendarEvent::where('tenant_id', $tenantId)
            ->with(['creator', 'meeting'])
            ->latest()
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'start' => $e->start_at->format('Y-m-d'),
                    'end' => $e->end_at ? $e->end_at->format('Y-m-d') : $e->start_at->format('Y-m-d'),
                    'backgroundColor' => $e->type === 'meeting' ? '#8b5cf6' : '#3b82f6',
                    'borderColor' => $e->type === 'meeting' ? '#7c3aed' : '#2563eb',
                ];
            });

        return Inertia::render('ERP/Calendar/Index', [
            'events' => $events
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string',
            'type' => 'required|string',
        ]);

        $tenantId = $request->user()->tenant_id;

        CalendarEvent::create([
            'tenant_id' => $tenantId,
            'title' => $request->title,
            'description' => $request->description,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'is_all_day' => $request->is_all_day ?? false,
            'location' => $request->location,
            'type' => $request->type,
            'status' => 'scheduled',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->back()->with('success', 'Event created successfully.');
    }

    public function update(Request $request, CalendarEvent $event)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_at' => 'required|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'is_all_day' => 'boolean',
            'location' => 'nullable|string',
            'type' => 'required|string',
            'status' => 'required|string',
        ]);

        $event->update($request->only(
            'title', 'description', 'start_at', 'end_at', 'is_all_day', 'location', 'type', 'status'
        ));

        return redirect()->back()->with('success', 'Event updated successfully.');
    }

    public function destroy(CalendarEvent $event)
    {
        $event->delete();
        return redirect()->back()->with('success', 'Event deleted successfully.');
    }
}
