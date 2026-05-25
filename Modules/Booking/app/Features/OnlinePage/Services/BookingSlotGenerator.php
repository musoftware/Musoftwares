<?php

namespace Modules\Booking\app\Features\OnlinePage\Services;

use Illuminate\Support\Carbon;

class BookingSlotGenerator
{
    protected $availabilityResolver;

    public function __construct(BookingAvailabilityResolver $availabilityResolver)
    {
        $this->availabilityResolver = $availabilityResolver;
    }

    /**
     * Generate 30 min available slots for a given day and resource
     */
    public function generateSlots(int $tenantId, int $resourceId, string $date, int $durationMinutes = 30): array
    {
        $startOfDay = Carbon::parse($date)->startOfDay()->addHours(9); // e.g. 9 AM
        $endOfDay = Carbon::parse($date)->startOfDay()->addHours(17); // e.g. 5 PM

        $slots = [];
        $current = $startOfDay->copy();

        while ($current->lessThan($endOfDay)) {
            if ($this->availabilityResolver->isAvailable($tenantId, $resourceId, $current, $durationMinutes)) {
                $slots[] = $current->format('H:i');
            }
            $current->addMinutes($durationMinutes);
        }

        return $slots;
    }
}
