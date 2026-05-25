<?php

namespace Modules\Booking\app\Features\Recurring\Services;

use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Modules\Booking\Models\Booking;
use Carbon\Carbon;

class RecurringOccurrenceGenerator
{
    protected $processor;
    protected $resolver;

    public function __construct(RecurrenceRuleProcessor $processor, RecurringConflictResolver $resolver)
    {
        $this->processor = $processor;
        $this->resolver = $resolver;
    }

    /**
     * Generates occurrences up to a given horizon (e.g. 30 days out)
     */
    public function generateForSeries(RecurringSeries $series, Carbon $horizon)
    {
        // Don't generate past the series explicitly defined end date
        if ($series->ends_at && $horizon->gt($series->ends_at)) {
            $horizon = $series->ends_at;
        }

        $dates = $this->processor->generateDates($series->rrule, $series->starts_at, $horizon);

        $generatedCount = 0;

        foreach ($dates as $date) {
            // Check if we already generated it
            $alreadyGenerated = Booking::where('recurring_series_id', $series->id)
                ->where('start_date', $date->format('Y-m-d'))
                ->exists();

            if ($alreadyGenerated) continue;

            // Check conflicts
            if (!$this->resolver->isSlotAvailable($date, $series)) continue;

            // Create occurrence
            Booking::create([
                'tenant_id' => $series->tenant_id,
                'recurring_series_id' => $series->id,
                'customer_id' => $series->customer_id,
                'resource_id' => $series->resource_id,
                'service_id' => $series->service_id,
                'start_date' => $date->format('Y-m-d'),
                'start_time' => $date->format('H:i:s'),
                'status' => 'confirmed'
            ]);

            $generatedCount++;
        }

        return $generatedCount;
    }
}
