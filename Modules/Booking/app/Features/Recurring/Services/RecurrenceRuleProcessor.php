<?php

namespace Modules\Booking\app\Features\Recurring\Services;

use Carbon\Carbon;

class RecurrenceRuleProcessor
{
    /**
     * A lightweight mock processor for RRULE evaluation.
     * In a full production env, this would wrap an iCal RRULE PHP library.
     * For our implementation, we'll build a fast WEEKLY/DAILY generator logic.
     */
    public function generateDates(string $rrule, Carbon $startsAt, Carbon $endsAtLimit)
    {
        $dates = [];
        $current = $startsAt->copy();
        
        // Mock RRULE logic: FREQ=WEEKLY
        if (strpos($rrule, 'FREQ=WEEKLY') !== false) {
            while ($current->lte($endsAtLimit)) {
                $dates[] = $current->copy();
                $current->addWeek();
            }
        } elseif (strpos($rrule, 'FREQ=DAILY') !== false) {
            while ($current->lte($endsAtLimit)) {
                $dates[] = $current->copy();
                $current->addDay();
            }
        } else {
            // Default fallback
            $dates[] = $startsAt->copy();
        }

        return $dates;
    }
}
