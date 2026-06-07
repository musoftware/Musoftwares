<?php

namespace Modules\Booking\app\Features\Scheduling;

use Carbon\Carbon;

class TimezoneManager
{
    /**
     * Convert a slot from the resource's local timezone to the workspace/customer timezone.
     *
     * @param string $datetime
     * @param string $fromTimezone
     * @param string $toTimezone
     * @return Carbon
     */
    public function convert(string $datetime, string $fromTimezone, string $toTimezone): Carbon
    {
        return Carbon::parse($datetime, $fromTimezone)->setTimezone($toTimezone);
    }
}
