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
            '{{customer_name}}' => $booking->customer->name ?? 'Customer',
            '{{service_name}}' => $booking->service->name ?? 'Appointment',
            '{{resource_name}}' => $booking->resource->name ?? 'Staff',
            '{{booking_date}}' => $booking->start_date ? \Carbon\Carbon::parse($booking->start_date)->format('Y-m-d') : '',
            '{{booking_time}}' => $booking->start_time ? \Carbon\Carbon::parse($booking->start_time)->format('H:i') : '',
        ];

        return strtr($templateContent, $replacements);
    }
}
