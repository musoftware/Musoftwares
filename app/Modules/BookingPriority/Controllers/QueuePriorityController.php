<?php

namespace App\Modules\BookingPriority\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\BookingPriority\Services\BookingPrioritySupportService;

class QueuePriorityController extends Controller
{
    protected BookingPrioritySupportService $priorityService;

    public function __construct(BookingPrioritySupportService $priorityService)
    {
        $this->priorityService = $priorityService;
    }

    public function index(Request $request, $branchId)
    {
        // Mock returning the ordered queue
        return response()->json(['data' => []]);
    }

    public function escalate(Request $request, $bookingId)
    {
        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        $this->priorityService->escalateBooking(
            1, // tenant_id
            $bookingId,
            $validated['reason'],
            auth()->id()
        );

        return response()->json(['message' => 'Booking escalated to emergency successfully']);
    }
}
