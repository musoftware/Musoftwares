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

        $this->mock(\Modules\Booking\app\Features\SmsNotifications\Services\BookingSmsLimitsService::class, function ($mock) {
            $mock->shouldReceive('canSendSms')->andReturn(true);
        });

        $tenantId = 1;
        
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

        $booking = new Booking();
        $booking->guest_phone = '01012345678';
        $booking->starts_at = \Carbon\Carbon::parse('2026-06-01 10:00:00');
        $booking->status = 'confirmed';
        $booking->tenant_id = $tenantId;
        $booking->id = 55;

        $service = app(BookingSmsNotificationService::class);
        $service->scheduleSms($booking, 'confirmation');

        Queue::assertPushed(SendSmsJob::class, function ($job) use ($booking) {
            return $job->bookingId === $booking->id && $job->mobile === '01012345678';
        });
    }
}
