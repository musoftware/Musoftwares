<?php

namespace Modules\Booking\app\Features\QueueManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;

class BookingQueueController extends Controller
{
    /**
     * Display a listing of the queues for the tenant.
     */
    public function index()
    {
        // Enforce SaaS feature flag
        if (!feature('booking.queue_management')) {
            abort(403, 'Unlock Queue Management: Manage walk-ins, waiting rooms, and realtime appointment queues.');
        }

        $queues = BookingQueue::withCount(['entries' => function ($q) {
            $q->where('status', 'waiting');
        }])->get();

        return response()->json($queues);
    }

    /**
     * Store a newly created queue.
     */
    public function store(Request $request)
    {
        if (!feature('booking.queue_management')) abort(403);

        // TODO: Enforce `max_active_queues` using BookingQueueLimitsService

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'prefix' => 'nullable|string|max:10',
            'branch_id' => 'nullable|integer',
            'resource_id' => 'nullable|integer',
        ]);

        $queue = BookingQueue::create($validated);

        return response()->json($queue, 201);
    }

    /**
     * Show a specific queue with its active entries.
     */
    public function show(BookingQueue $queue)
    {
        if (!feature('booking.queue_management')) abort(403);

        $queue->load(['entries' => function ($query) {
            $query->whereIn('status', ['waiting', 'called', 'serving'])
                  ->orderBy('status')
                  ->orderBy('priority_level', 'desc')
                  ->orderBy('sequence_number', 'asc');
        }]);

        return response()->json($queue);
    }
}
