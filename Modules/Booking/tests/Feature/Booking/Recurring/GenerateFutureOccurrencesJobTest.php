<?php

namespace Modules\Booking\tests\Feature\Booking\Recurring;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Modules\Booking\app\Features\Recurring\Jobs\GenerateFutureOccurrencesJob;
use Modules\Booking\app\Features\Recurring\Services\RecurringOccurrenceGenerator;
use Modules\Booking\app\Features\Recurring\Services\RecurrenceRuleProcessor;
use Modules\Booking\app\Features\Recurring\Services\RecurringConflictResolver;
use Modules\Booking\Models\Booking;
use Illuminate\Support\Carbon;

class GenerateFutureOccurrencesJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_lazy_generates_occurrences_into_core_bookings_table()
    {
        $series = RecurringSeries::create([
            'tenant_id' => 1,
            'customer_id' => 10,
            'resource_id' => 5,
            'rrule' => 'FREQ=DAILY',
            'starts_at' => Carbon::now(), // Will generate 30 days out
        ]);

        $job = new GenerateFutureOccurrencesJob($series->id);
        
        $generator = new RecurringOccurrenceGenerator(
            new RecurrenceRuleProcessor(),
            new RecurringConflictResolver()
        );

        $job->handle($generator);

        // Verify it generated occurrences into the global Booking table
        $count = Booking::where('recurring_series_id', $series->id)->count();
        
        // Starts at today + 30 days = 31 total occurrences generated lazily
        $this->assertEquals(31, $count);
    }
}
