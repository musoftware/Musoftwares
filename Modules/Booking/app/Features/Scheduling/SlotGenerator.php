<?php

namespace Modules\Booking\Features\Scheduling;

class SlotGenerator
{
    /**
     * Generate available time slots based on resource schedule, exceptions, buffers, and existing reservations.
     *
     * @param int $resourceId
     * @param string $startDate
     * @param string $endDate
     * @param int $serviceDuration
     * @return array
     */
    public function generate(int $resourceId, string $startDate, string $endDate, int $serviceDuration): array
    {
        $resource = \Modules\Booking\Features\Resources\BookingResource::with([
            'schedules',
            'exceptions' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate]);
            },
            'timeOffs' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_time', [$startDate, $endDate]);
            }
        ])->find($resourceId);

        if (!$resource || !$resource->is_active) {
            return [];
        }

        $reservations = \Modules\Booking\Features\Reservations\BookingReservation::where('resource_id', $resourceId)
            ->whereIn('status', ['confirmed', 'checked_in', 'in_progress'])
            ->whereBetween('start_at', [$startDate, $endDate])
            ->get();

        $slots = [];
        $currentDate = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        // A simplified daily slot generation loop
        while ($currentDate->lte($end)) {
            $dayOfWeek = strtolower($currentDate->format('l'));
            $schedule = $resource->schedules->where('day_of_week', $dayOfWeek)->first();

            if ($schedule) {
                $startTime = \Carbon\Carbon::parse($currentDate->format('Y-m-d') . ' ' . $schedule->start_time);
                $endTime = \Carbon\Carbon::parse($currentDate->format('Y-m-d') . ' ' . $schedule->end_time);

                while ($startTime->copy()->addMinutes($serviceDuration)->lte($endTime)) {
                    $slotStart = $startTime->copy()->addMinutes($resource->buffer_before);
                    $slotEnd = $slotStart->copy()->addMinutes($serviceDuration);
                    
                    // Simple collision check logic
                    $hasCollision = $reservations->contains(function ($res) use ($slotStart, $slotEnd) {
                        return $slotStart->lt($res->end_at) && $slotEnd->gt($res->start_at);
                    });

                    if (!$hasCollision) {
                        $slots[] = [
                            'start_at' => $slotStart->toDateTimeString(),
                            'end_at' => $slotEnd->toDateTimeString(),
                        ];
                    }

                    $startTime->addMinutes($serviceDuration + $resource->buffer_before + $resource->buffer_after);
                }
            }
            $currentDate->addDay();
        }

        return $slots;
    }
}
