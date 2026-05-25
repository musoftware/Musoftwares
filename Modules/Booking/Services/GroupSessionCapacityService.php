<?php

namespace Modules\Booking\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingEventType;
use App\Exceptions\SaaSLimitExceededException;
use Carbon\Carbon;

class GroupSessionCapacityService
{
    /**
     * Checks if a booking slot has remaining capacity.
     * Throws an exception if capacity is full or slot is unavailable.
     */
    public function enforceCapacity(BookingEventType $eventType, Carbon $startsAt, ?int $providerId = null)
    {
        $buffer = $eventType->buffer_before ?? 0;
        $endsAt = $startsAt->copy()->addMinutes($eventType->duration_minutes);

        $bookingsQuery = Booking::where('booking_event_type_id', $eventType->id)
            ->whereIn('status', ['pending', 'confirmed']);

        if ($providerId) {
            $bookingsQuery->where('booking_provider_id', $providerId);
        }

        // Get bookings that overlap with this exact time slot
        $overlappingBookings = $bookingsQuery->where(function($q) use ($startsAt, $endsAt, $buffer) {
            $q->where(function($sub) use ($startsAt, $endsAt, $buffer) {
                // Approximate overlap check for exact slot logic
                $sub->where('starts_at', '<', $endsAt)
                    ->where('ends_at', '>', $startsAt);
            });
        })->count();

        if ($overlappingBookings > 0) {
            if (!$eventType->is_group_session) {
                throw new \Exception("This time slot is no longer available.");
            }

            if ($overlappingBookings >= $eventType->capacity) {
                throw new \Exception("Group session capacity is full. Maximum {$eventType->capacity} participants allowed.");
            }
        }
        
        return $eventType->is_group_session ? ($eventType->capacity - $overlappingBookings) : 0;
    }
}
