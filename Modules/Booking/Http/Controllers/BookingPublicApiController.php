<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Booking\Services\AvailabilityEngine;
use Carbon\Carbon;

class BookingPublicApiController extends Controller
{
    protected $availabilityEngine;

    public function __construct(AvailabilityEngine $availabilityEngine)
    {
        $this->availabilityEngine = $availabilityEngine;
    }

    /**
     * Get available slots for an event type.
     */
    public function getSlots(Request $request, $eventTypeId)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'timezone' => 'nullable|string',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $timezone = $request->timezone ?? 'UTC';

        // Additional validation: don't allow fetching more than 60 days at a time to prevent overload
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        if ($start->diffInDays($end) > 60) {
            return response()->json(['error' => 'Date range cannot exceed 60 days.'], 400);
        }

        $slots = $this->availabilityEngine->generateSlots($eventTypeId, $startDate, $endDate, $timezone);

        return response()->json([
            'data' => $slots
        ]);
    }

    /**
     * Acquire a temporary lock for a slot.
     */
    public function acquireLock(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|integer',
            'starts_at' => 'required|date',
            'duration_minutes' => 'required|integer'
        ]);

        $providerId = $request->provider_id;
        $startsAt = Carbon::parse($request->starts_at);
        $lockKey = "booking_slot_{$providerId}_" . $startsAt->timestamp;

        // Check if already locked or booked
        if (\Illuminate\Support\Facades\Cache::has($lockKey)) {
            return response()->json(['error' => 'Slot is currently locked by another user.'], 409);
        }

        // Lock for 15 minutes
        \Illuminate\Support\Facades\Cache::put($lockKey, true, now()->addMinutes(15));

        return response()->json([
            'message' => 'Slot locked successfully for 15 minutes.',
            'expires_at' => now()->addMinutes(15)->toIso8601String()
        ]);
    }
}
