<?php

namespace Modules\Booking\Tests\Feature;

use Tests\TestCase;
use Modules\Booking\Models\BookingDailyMetric;
use Modules\Booking\Features\Analytics\Services\BookingAnalyticsService;
use Modules\Booking\Features\Analytics\Listeners\UpdateDailyMetricsListener;
use Modules\Booking\Events\BookingStatusChanged;
use Modules\Booking\Models\Booking;
use Modules\Booking\Models\BookingEventType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

class BookingAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_listener_increments_metrics_correctly()
    {
        $tenant = User::forceCreate([
            'name' => 'Clinic 1', 
            'email' => 't1@test.com', 
            'password' => 'test',
            'tenant_id' => 1
        ]);
        
        $eventType = BookingEventType::forceCreate([
            'user_id' => $tenant->id,
            'title' => 'Consultation',
            'slug' => 'consult',
            'duration_minutes' => 30,
            'price' => 150.00,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'booking_event_type_id' => $eventType->id,
            'guest_name' => 'Alice',
            'guest_email' => 'alice@test.com',
            'starts_at' => Carbon::now()->startOfDay()->addHours(10),
            'ends_at' => Carbon::now()->startOfDay()->addHours(10)->addMinutes(30),
            'timezone' => 'UTC',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'price' => 150.00,
            'currency' => 'USD'
        ]);

        // Manually trigger listener
        $listener = new UpdateDailyMetricsListener();
        $listener->handle(new BookingStatusChanged($booking, 'confirmed'));

        $metric = BookingDailyMetric::where('tenant_id', 1)->first();
        
        $this->assertNotNull($metric);
        $this->assertEquals(1, $metric->total_bookings);
        $this->assertEquals(150.00, $metric->total_revenue);
        $this->assertEquals('USD', $metric->currency);
        
        // Simulate completion
        $booking->status = 'completed';
        $listener->handle(new BookingStatusChanged($booking, 'completed'));
        
        $metric->refresh();
        $this->assertEquals(1, $metric->completed_bookings);
    }

    public function test_analytics_service_rolls_up_data()
    {
        BookingDailyMetric::create([
            'tenant_id' => 2,
            'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
            'total_bookings' => 5,
            'completed_bookings' => 4,
            'no_show_bookings' => 1,
            'total_revenue' => 500.00,
            'currency' => 'EGP'
        ]);
        
        BookingDailyMetric::create([
            'tenant_id' => 2,
            'date' => Carbon::now()->subDays(1)->format('Y-m-d'),
            'total_bookings' => 10,
            'completed_bookings' => 9,
            'no_show_bookings' => 1,
            'total_revenue' => 1000.00,
            'currency' => 'EGP'
        ]);

        $service = new BookingAnalyticsService();
        $summary = $service->getSummary(2, Carbon::now()->subDays(7)->format('Y-m-d'), Carbon::now()->format('Y-m-d'));

        $this->assertEquals(15, $summary['total_bookings']);
        $this->assertEquals(13, $summary['completed_bookings']);
        $this->assertEquals(2, $summary['no_show_bookings']);
        // No show rate: 2 / 15 = 13.33%
        $this->assertEquals(13.33, $summary['no_show_rate_percent']);
        $this->assertEquals(1500.00, $summary['revenue_by_currency']['EGP']);
    }
}
