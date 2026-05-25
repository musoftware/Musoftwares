<?php

namespace Modules\Booking\tests\Feature\Booking\Recurring;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\app\Features\Recurring\Models\RecurringSeries;
use Modules\Booking\app\Features\Recurring\Models\RecurringException;
use Modules\Booking\app\Features\Recurring\Services\RecurringConflictResolver;
use Modules\Booking\Models\Booking;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Modules\Booking\app\Features\Recurring\Events\OccurrenceSkipped;

class RecurringConflictResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_detects_existing_booking_conflict()
    {
        Event::fake();

        $series = RecurringSeries::create([
            'tenant_id' => 1,
            'customer_id' => 10,
            'resource_id' => 5,
            'rrule' => 'FREQ=WEEKLY',
            'starts_at' => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        // Create an existing single booking that blocks the slot
        Booking::create([
            'tenant_id' => 1,
            'customer_id' => 99,
            'resource_id' => 5, // Same resource
            'start_date' => '2026-06-01',
            'start_time' => '10:00:00',
            'status' => 'confirmed'
        ]);

        $resolver = new RecurringConflictResolver();
        
        $isAvailable = $resolver->isSlotAvailable(Carbon::parse('2026-06-01 10:00:00'), $series);
        
        $this->assertFalse($isAvailable);
        Event::assertDispatched(OccurrenceSkipped::class);
    }

    public function test_resolver_respects_holiday_exceptions()
    {
        Event::fake();

        $series = RecurringSeries::create([
            'tenant_id' => 1,
            'customer_id' => 10,
            'resource_id' => 5,
            'rrule' => 'FREQ=WEEKLY',
            'starts_at' => Carbon::parse('2026-06-01 10:00:00'),
        ]);

        // Mark date as an exception (e.g. holiday)
        RecurringException::create([
            'tenant_id' => 1,
            'series_id' => $series->id,
            'exception_date' => '2026-06-08',
            'reason' => 'holiday'
        ]);

        $resolver = new RecurringConflictResolver();
        
        $isAvailable = $resolver->isSlotAvailable(Carbon::parse('2026-06-08 10:00:00'), $series);
        
        $this->assertFalse($isAvailable);
        Event::assertDispatched(OccurrenceSkipped::class);
    }
}
