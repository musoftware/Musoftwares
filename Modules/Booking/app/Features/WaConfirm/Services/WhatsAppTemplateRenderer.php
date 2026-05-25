<?php

namespace Modules\Booking\app\Features\WaConfirm\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaTemplate;

class WhatsAppTemplateRenderer
{
    /**
     * Replaces dynamic variables like {{customer_name}} with real data.
     */
    public function render(BookingWaTemplate $template, Booking $booking, array $actionLinks): string
    {
        $body = $template->body;

        $replacements = [
            '{{customer_name}}' => $booking->customer->name ?? 'Customer',
            '{{booking_date}}' => $booking->start_time ? $booking->start_time->format('Y-m-d') : 'TBD',
            '{{booking_time}}' => $booking->start_time ? $booking->start_time->format('H:i') : 'TBD',
            '{{confirmation_link}}' => $actionLinks['confirm'] ?? '',
            '{{cancel_link}}' => $actionLinks['cancel'] ?? '',
            '{{reschedule_link}}' => $actionLinks['reschedule'] ?? '',
        ];

        foreach ($replacements as $key => $value) {
            $body = str_replace($key, $value, $body);
        }

        return $body;
    }
}
