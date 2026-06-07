<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services;

use Modules\Booking\Models\Booking;

class SmsTemplateRenderer
{
    /**
     * Replaces {{placeholders}} in the SMS template with actual booking data.
     */
    public function render(string $templateContent, Booking $booking): string
    {
        $replacements = [
            '{{customer_name}}' => $booking->clientUser->name ?? $booking->guest_name ?? 'Customer',
            '{{service_name}}' => $booking->eventType->title ?? 'Appointment',
            '{{resource_name}}' => $booking->provider->name ?? 'Staff',
            '{{booking_date}}' => $booking->starts_at ? \Carbon\Carbon::parse($booking->starts_at)->format('Y-m-d') : '',
            '{{booking_time}}' => $booking->starts_at ? \Carbon\Carbon::parse($booking->starts_at)->format('H:i') : '',
        ];

        return strtr($templateContent, $replacements);
    }
}
