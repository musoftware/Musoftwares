<?php

namespace Modules\Booking\app\Features\WaReminders\Services;

use Modules\Booking\Models\Booking;
use Illuminate\Support\Facades\URL;

class WhatsAppTemplateRenderer
{
    /**
     * Render the template with booking specific data.
     */
    public function render(string $templateContent, Booking $booking): string
    {
        $replacements = [
            '{{customer_name}}' => $booking->customer->name ?? 'Customer',
            '{{service_name}}' => $booking->service->name ?? 'Service',
            '{{booking_date}}' => $booking->starts_at->format('Y-m-d'),
            '{{booking_time}}' => $booking->starts_at->format('H:i'),
            '{{booking_link}}' => $this->generateSignedConfirmLink($booking),
            '{{cancel_link}}' => $this->generateSignedCancelLink($booking),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $templateContent);
    }

    protected function generateSignedConfirmLink(Booking $booking): string
    {
        // Example signed route for confirmation
        return URL::signedRoute('booking.public.confirm', ['uuid' => $booking->uuid ?? $booking->id]);
    }

    protected function generateSignedCancelLink(Booking $booking): string
    {
        return URL::signedRoute('booking.public.cancel', ['uuid' => $booking->uuid ?? $booking->id]);
    }
}
