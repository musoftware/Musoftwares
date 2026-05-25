<?php

namespace Modules\Booking\tests\Unit\Booking\OnlinePage;

use Tests\TestCase;
use Modules\Booking\app\Features\OnlinePage\Services\BookingSlotGenerator;
use Modules\Booking\app\Features\OnlinePage\Services\BookingAvailabilityResolver;
use Mockery;
use Illuminate\Support\Carbon;

class BookingSlotGeneratorTest extends TestCase
{
    public function test_generates_slots_based_on_availability()
    {
        $resolver = Mockery::mock(BookingAvailabilityResolver::class);
        
        // Mock resolver to always return true (available)
        $resolver->shouldReceive('isAvailable')->andReturn(true);

        $generator = new BookingSlotGenerator($resolver);
        
        // Generate for a specific day
        $slots = $generator->generateSlots(1, 1, '2026-05-26', 30);
        
        $this->assertNotEmpty($slots);
        $this->assertContains('09:00', $slots);
        $this->assertContains('09:30', $slots);
    }
}
