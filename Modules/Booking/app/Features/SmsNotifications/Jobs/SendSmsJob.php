<?php

namespace Modules\Booking\app\Features\SmsNotifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsSetting;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsLog;
use Modules\Booking\app\Features\SmsNotifications\Services\Providers\SmsProviderManager;
use Modules\Booking\app\Features\SmsNotifications\Events\BookingSmsSent;
use Modules\Booking\app\Features\SmsNotifications\Events\BookingSmsFailed;
use Modules\Booking\Models\Booking;
use Exception;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $bookingId;
    public $mobile;
    public $message;
    public $settingId;

    public $tries = 3;
    public $backoff = [10, 60, 300]; // 10s, 1m, 5m retries

    public function __construct(int $bookingId, string $mobile, string $message, int $settingId)
    {
        $this->bookingId = $bookingId;
        $this->mobile = $mobile;
        $this->message = $message;
        $this->settingId = $settingId;
    }

    public function handle(SmsProviderManager $manager): void
    {
        $setting = SmsSetting::find($this->settingId);
        $booking = Booking::find($this->bookingId);

        if (!$setting || !$booking) return;

        // Create log entry as queued
        $log = SmsLog::create([
            'tenant_id' => $setting->tenant_id,
            'booking_id' => $booking->id,
            'provider' => $setting->provider_name,
            'mobile' => $this->mobile,
            'content' => $this->message,
            'status' => 'queued',
        ]);

        try {
            $provider = $manager->resolve($setting->provider_name);
            $success = $provider->send($this->mobile, $this->message, $setting->provider_credentials ?? []);

            if ($success) {
                $log->update(['status' => 'sent']);
                event(new BookingSmsSent($log));
            } else {
                $log->update(['status' => 'failed', 'error_message' => 'Provider returned false']);
                event(new BookingSmsFailed($log));
            }
        } catch (Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            event(new BookingSmsFailed($log));
            throw $e; // Rethrow to trigger queue retry
        }
    }
}
