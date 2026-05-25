<?php

namespace Modules\Booking\app\Features\QueueManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueue;
use Modules\Booking\app\Features\QueueManagement\Models\BookingQueueEntry;
use Modules\Booking\app\Features\QueueManagement\Services\BookingQueueService;
use Modules\Booking\app\Features\QueueManagement\Services\QueueFlowManager;

class QueueEntryController extends Controller
{
    protected $queueService;
    protected $flowManager;

    public function __construct(BookingQueueService $queueService, QueueFlowManager $flowManager)
    {
        $this->queueService = $queueService;
        $this->flowManager = $flowManager;
    }

    /**
     * Check-in a customer (generates token).
     */
    public function checkIn(Request $request, BookingQueue $queue)
    {
        if (!feature('booking.queue_management')) abort(403);

        $validated = $request->validate([
            'walkin_name' => 'nullable|string|max:255',
            'walkin_phone' => 'nullable|string|max:20',
            'booking_id' => 'nullable|integer',
            'priority_level' => 'nullable|integer',
        ]);

        $entry = $this->queueService->checkIn($queue, $validated);

        return response()->json($entry, 201);
    }

    /**
     * Call the next person in the queue.
     */
    public function callNext(BookingQueue $queue)
    {
        if (!feature('booking.queue_management')) abort(403);

        $nextEntry = $this->flowManager->callNext($queue);

        if (!$nextEntry) {
            return response()->json(['message' => 'Queue is empty.'], 404);
        }

        return response()->json($nextEntry);
    }

    /**
     * Mark an entry as completed.
     */
    public function complete(BookingQueueEntry $entry)
    {
        if (!feature('booking.queue_management')) abort(403);

        $this->flowManager->complete($entry);

        return response()->json(['message' => 'Entry completed.']);
    }

    /**
     * Skip an entry.
     */
    public function skip(BookingQueueEntry $entry)
    {
        if (!feature('booking.queue_management')) abort(403);

        $this->flowManager->skip($entry);

        return response()->json(['message' => 'Entry skipped.']);
    }
}
