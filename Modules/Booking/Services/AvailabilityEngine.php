<?php

namespace Modules\Booking\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingProvider;
use Modules\Booking\Models\BookingBlockedDate;
use Modules\Booking\Models\BookingAvailabilityRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class AvailabilityEngine
{
    /**
     * Generate available slots for a given service and date range.
     * 
     * @param int $eventTypeId Service ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @param string $timezone Timezone string
     * @return array
     */
    public function generateSlots($eventTypeId, $startDate, $endDate, $timezone = 'UTC')
    {
        $providers = BookingProvider::whereHas('eventTypes', function ($q) use ($eventTypeId) {
            $q->where('booking_event_type_id', $eventTypeId);
        })->where('is_active', true)->get();

        $eventType = \Modules\Booking\Models\BookingEventType::findOrFail($eventTypeId);
        $duration = $eventType->duration_minutes;
        $buffer = $eventType->buffer_time ?? 0; // Assuming we add buffer_time to EventType

        $start = Carbon::parse($startDate, $timezone)->startOfDay();
        $end = Carbon::parse($endDate, $timezone)->endOfDay();

        $slots = [];

        foreach ($providers as $provider) {
            $providerSlots = $this->calculateProviderSlots($provider, $eventType, $start, $end, $duration, $buffer, $timezone);
            foreach ($providerSlots as $slot) {
                $dateKey = $slot['start']->format('Y-m-d');
                if (!isset($slots[$dateKey])) {
                    $slots[$dateKey] = [];
                }
                
                $slotStr = $slot['start']->format('H:i');
                if (!isset($slots[$dateKey][$slotStr])) {
                    $slots[$dateKey][$slotStr] = [
                        'start_time' => $slotStr,
                        'available_providers' => []
                    ];
                }
                $slots[$dateKey][$slotStr]['available_providers'][] = $provider->id;
            }
        }

        return $slots;
    }

    private function calculateProviderSlots($provider, $eventType, $startDate, $endDate, $duration, $buffer, $timezone)
    {
        $rules = BookingAvailabilityRule::where('booking_provider_id', $provider->id)
            ->where('is_enabled', true)
            ->get();
            
        $exceptions = BookingBlockedDate::where('booking_provider_id', $provider->id)
            ->where(function($query) use ($startDate, $endDate) {
                $query->whereBetween('starts_at', [$startDate, $endDate])
                      ->orWhereBetween('ends_at', [$startDate, $endDate])
                      ->orWhere('is_recurring', true);
            })->get();
            
        $existingBookings = Booking::where('booking_provider_id', $provider->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->get();

        $slots = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $weekday = $currentDate->dayOfWeek;
            
            // Get applicable rules for this day
            $dayRules = $rules->filter(function ($rule) use ($weekday, $currentDate) {
                if ($rule->type === 'recurring' && $rule->weekday == $weekday) {
                    return true;
                }
                if ($rule->type === 'one-time' && $rule->date == $currentDate->format('Y-m-d')) {
                    return true;
                }
                return false;
            });

            foreach ($dayRules as $rule) {
                $ruleStart = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $rule->start_time, $timezone);
                $ruleEnd = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $rule->end_time, $timezone);

                $slotStart = $ruleStart->copy();
                
                while ($slotStart->copy()->addMinutes($duration) <= $ruleEnd) {
                    $slotEnd = $slotStart->copy()->addMinutes($duration);
                    
                    if ($this->isSlotAvailable($slotStart, $slotEnd, $exceptions, $existingBookings, $buffer, $provider->id, $eventType)) {
                        $slots[] = [
                            'start' => $slotStart->copy(),
                            'end' => $slotEnd->copy(),
                        ];
                    }
                    
                    // Move to next slot considering buffer
                    $slotStart->addMinutes($duration + $buffer);
                }
            }
            $currentDate->addDay();
        }

        return $slots;
    }

    private function isSlotAvailable($start, $end, $exceptions, $bookings, $buffer, $providerId, $eventType)
    {
        // Check temporary locks
        $lockKey = "booking_slot_{$providerId}_" . $start->timestamp;
        if (Cache::has($lockKey)) {
            return false;
        }

        // Check Exceptions
        foreach ($exceptions as $ex) {
            $exStart = Carbon::parse($ex->starts_at);
            $exEnd = Carbon::parse($ex->ends_at);
            
            if ($ex->is_recurring && $ex->recurring_pattern) {
                // e.g. "weekly_friday"
                if ($ex->recurring_pattern === 'weekly_friday' && $start->isFriday()) {
                    // Match time portions
                    $exStartToday = $start->copy()->setTimeFrom($exStart);
                    $exEndToday = $start->copy()->setTimeFrom($exEnd);
                    if ($this->overlaps($start, $end, $exStartToday, $exEndToday)) {
                        return false;
                    }
                }
            } else {
                if ($this->overlaps($start, $end, $exStart, $exEnd)) {
                    return false;
                }
            }
        }

        // Check Bookings
        $overlappingBookings = 0;
        foreach ($bookings as $booking) {
            $bStart = Carbon::parse($booking->starts_at)->subMinutes($buffer);
            $bEnd = Carbon::parse($booking->ends_at)->addMinutes($buffer);
            
            if ($this->overlaps($start, $end, $bStart, $bEnd)) {
                $overlappingBookings++;
            }
        }

        if ($overlappingBookings > 0) {
            if ($eventType->is_group_session && $overlappingBookings < $eventType->capacity) {
                // Allow booking for group session up to capacity
                return true;
            }
            return false;
        }

        return true;
    }

    private function overlaps($start1, $end1, $start2, $end2)
    {
        return $start1 < $end2 && $end1 > $start2;
    }
}
