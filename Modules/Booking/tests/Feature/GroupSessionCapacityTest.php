<?php

namespace Modules\Booking\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingEventType;
use Modules\Booking\Services\GroupSessionCapacityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class GroupSessionCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_session_enforces_capacity()
    {
        Event::fake();
        
        $host = User::forceCreate([
            'name' => 'Host', 
            'email' => 'host@test.com', 
            'password' => 'test'
        ]);
        
        $eventType = BookingEventType::forceCreate([
            'user_id' => $host->id,
            'title' => 'Yoga Class',
            'slug' => 'yoga-class',
            'is_group_session' => true,
            'capacity' => 2,
            'duration_minutes' => 60,
            'price' => 0,
            'requires_payment' => false,
            'is_active' => true,
        ]);

        $startsAt = Carbon::now()->addDay()->startOfHour();
        
        $service = new GroupSessionCapacityService();

        // 1st Booking (Success)
        Booking::create([
            'booking_event_type_id' => $eventType->id,
            'guest_name' => 'Alice',
            'guest_email' => 'alice@test.com',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(60),
            'timezone' => 'UTC',
            'status' => 'confirmed',
            'payment_status' => 'free',
        ]);
        
        // Check capacity allows 1 more
        $service->enforceCapacity($eventType, $startsAt);

        // 2nd Booking (Success)
        Booking::create([
            'booking_event_type_id' => $eventType->id,
            'guest_name' => 'Bob',
            'guest_email' => 'bob@test.com',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes(60),
            'timezone' => 'UTC',
            'status' => 'confirmed',
            'payment_status' => 'free',
        ]);

        // 3rd Booking (Throws Exception)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Group session capacity is full.");
        $service->enforceCapacity($eventType, $startsAt);
    }
}
