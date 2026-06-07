<?php

namespace Modules\Booking\app\Features\Scheduling;

class ConflictValidator
{
    /**
     * Validate if a new reservation conflicts with existing bookings, exceptions, or capacity limits.
     *
     * @param int $resourceId
     * @param string $startAt
     * @param string $endAt
     * @param int $capacityRequired
     * @return bool
     */
    public function hasConflict(int $resourceId, string $startAt, string $endAt, int $capacityRequired = 1): bool
    {
        // 1. Check for overlapping reservations
        $overlappingReservationsCount = \Modules\Booking\app\Features\Reservations\BookingReservation::where('resource_id', $resourceId)
            ->whereIn('status', ['confirmed', 'checked_in', 'in_progress'])
            ->where(function ($query) use ($startAt, $endAt) {
                $query->where(function ($q) use ($startAt, $endAt) {
                    $q->where('start_at', '<', $endAt)
                      ->where('end_at', '>', $startAt);
                });
            })->count();

        // If the overlapping count + new required capacity > resource max capacity, then conflict
        // (Assuming max capacity is 1 for non-group resources)
        $resource = \Modules\Booking\app\Features\Resources\BookingResource::find($resourceId);
        if (!$resource || !$resource->is_active) {
            return true;
        }

        // For simplicity, we assume resource capacity is 1. If group session, we'd check service capacity.
        $resourceCapacity = 1; 

        if (($overlappingReservationsCount + $capacityRequired) > $resourceCapacity) {
            return true;
        }

        // 2. Check if the slot falls inside a schedule exception
        $hasException = \Modules\Booking\app\Features\Availability\BookingScheduleException::where('resource_id', $resourceId)
            ->where('start_date', '<=', \Carbon\Carbon::parse($startAt)->toDateString())
            ->where('end_date', '>=', \Carbon\Carbon::parse($endAt)->toDateString())
            ->exists();

        if ($hasException) {
            return true;
        }

        return false;
    }
}
