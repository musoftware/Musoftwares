<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsTemplate;
use Modules\Booking\app\Features\SmsNotifications\Models\SmsSetting;
use Modules\Booking\app\Features\SmsNotifications\Jobs\SendSmsJob;
use Illuminate\Support\Carbon;

class BookingSmsNotificationService
{
    protected $renderer;
    protected $limits;

    public function __construct(SmsTemplateRenderer $renderer, BookingSmsLimitsService $limits)
    {
        $this->renderer = $renderer;
        $this->limits = $limits;
    }

    public function scheduleSms(Booking $booking, string $type, Carbon $delay = null)
    {
        if (!$this->limits->canSendSms($booking->tenant_id)) {
            return;
        }

        $setting = SmsSetting::where('tenant_id', $booking->tenant_id)->where('is_active', true)->first();
        if (!$setting) return;

        $template = SmsTemplate::where('tenant_id', $booking->tenant_id)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if (!$template) return;

        $message = $this->renderer->render($template->content, $booking);
        $mobile = $booking->customer->phone ?? null;

        if (!$mobile) return;

        $job = new SendSmsJob($booking->id, $mobile, $message, $setting->id);

        if ($delay) {
            dispatch($job)->delay($delay);
        } else {
            dispatch($job);
        }
    }
}
