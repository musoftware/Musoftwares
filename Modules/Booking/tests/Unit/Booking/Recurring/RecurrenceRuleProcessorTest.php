<?php

namespace Modules\Booking\tests\Unit\Booking\Recurring;

use Tests\TestCase;
use Modules\Booking\app\Features\Recurring\Services\RecurrenceRuleProcessor;
use Illuminate\Support\Carbon;

class RecurrenceRuleProcessorTest extends TestCase
{
    public function test_evaluates_weekly_rrule_correctly()
    {
        $processor = new RecurrenceRuleProcessor();
        
        $startsAt = Carbon::parse('2026-05-25 10:00:00');
        $endsAtLimit = Carbon::parse('2026-06-15 10:00:00'); // 3 weeks later
        
        $dates = $processor->generateDates('FREQ=WEEKLY', $startsAt, $endsAtLimit);

        $this->assertCount(4, $dates);
        $this->assertEquals('2026-05-25', $dates[0]->format('Y-m-d'));
        $this->assertEquals('2026-06-01', $dates[1]->format('Y-m-d'));
        $this->assertEquals('2026-06-08', $dates[2]->format('Y-m-d'));
        $this->assertEquals('2026-06-15', $dates[3]->format('Y-m-d'));
    }
}
