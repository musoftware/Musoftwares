<?php

namespace Modules\Booking\tests\Feature\Booking\SmsNotifications;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Booking\Models\Booking;
use App\Models\User;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsSetting;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsTemplate;
use Modules\Booking\app\Features\SmsNotifications\Services\BookingSmsNotificationService;
use Modules\Booking\app\Features\SmsNotifications\Jobs\SendSmsJob;

class BookingSmsNotificationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_triggers_sms_job()
    {
        Queue::fake();

        $tenantId = 1;
        $customer = User::factory()->create(['tenant_id' => $tenantId, 'phone' => '01012345678']);

        SmsSetting::create([
            'tenant_id' => $tenantId,
            'provider_name' => 'twilio',
            'provider_credentials' => ['account_sid' => 'test', 'auth_token' => 'test'],
            'is_active' => true
        ]);

        SmsTemplate::create([
            'tenant_id' => $tenantId,
            'type' => 'confirmation',
            'content' => 'Your booking is confirmed.',
            'is_active' => true
        ]);

        $booking = Booking::create([
            'tenant_id' => $tenantId,
            'customer_id' => $customer->id,
            'start_date' => '2026-06-01',
            'start_time' => '10:00:00',
            'status' => 'confirmed'
        ]);

        $service = app(BookingSmsNotificationService::class);
        $service->scheduleSms($booking, 'confirmation');

        Queue::assertPushed(SendSmsJob::class, function ($job) use ($booking, $customer) {
            return $job->bookingId === $booking->id && $job->mobile === $customer->phone;
        });
    }
}
