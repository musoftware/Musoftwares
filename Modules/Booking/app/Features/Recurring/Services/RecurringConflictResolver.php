<?php

namespace Modules\Booking\app\Features\Recurring\Services;

use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Modules\Booking\Models\Booking;
use Carbon\Carbon;
use Modules\Booking\app\Features\Recurring\Events\OccurrenceSkipped;

class RecurringConflictResolver
{
    /**
     * Resolves conflict. True = No conflict. False = conflict detected.
     */
    public function isSlotAvailable(Carbon $date, RecurringSeries $series): bool
    {
        // Check if there is already a booking for this resource at this exact time
        $conflict = Booking::where('tenant_id', $series->tenant_id)
            ->where('resource_id', $series->resource_id)
            ->where('start_date', $date->format('Y-m-d'))
            ->where('start_time', $date->format('H:i:s'))
            ->exists();

        if ($conflict) {
            // Log it and fire event
            event(new OccurrenceSkipped($series, $date, 'Resource already booked'));
            return false;
        }

        // Check if it's an exception (e.g. holiday)
        $isException = $series->exceptions()
            ->where('exception_date', $date->format('Y-m-d'))
            ->exists();

        if ($isException) {
            event(new OccurrenceSkipped($series, $date, 'Manually skipped or holiday'));
            return false;
        }

        return true;
    }
}
