<?php

namespace Modules\Booking\app\Features\WaConfirm\Services;

use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaTemplate;
use Modules\Booking\app\Features\WaConfirm\Jobs\SendBookingConfirmationJob;

class BookingWhatsAppConfirmationService
{
    protected $linkGenerator;
    protected $templateRenderer;
    protected $limitsService;

    public function __construct(
        BookingActionLinkGenerator $linkGenerator, 
        WhatsAppTemplateRenderer $templateRenderer,
        BookingWhatsAppConfirmLimitsService $limitsService
    ) {
        $this->linkGenerator = $linkGenerator;
        $this->templateRenderer = $templateRenderer;
        $this->limitsService = $limitsService;
    }

    /**
     * Orchestrates the creation and dispatching of a WhatsApp confirmation.
     */
    public function dispatchConfirmation(Booking $booking, BookingWaTemplate $template)
    {
        $this->limitsService->enforce('monthly_confirmation_messages');

        // 1. Create the base confirmation record (Status: pending)
        $confirmation = BookingWaConfirmation::create([
            'tenant_id' => $booking->tenant_id,
            'booking_id' => $booking->id,
            'status' => 'pending',
            'expires_at' => $booking->start_time ? $booking->start_time->subHours(2) : now()->addDays(7),
        ]);

        // 2. Generate the secure action links
        $actionLinks = [
            'confirm' => $this->linkGenerator->generateLink($confirmation, 'confirm'),
            'cancel' => $this->linkGenerator->generateLink($confirmation, 'cancel'),
            'reschedule' => $this->linkGenerator->generateLink($confirmation, 'reschedule'),
        ];

        // 3. Render the final text payload
        $messageBody = $this->templateRenderer->render($template, $booking, $actionLinks);

        // 4. Dispatch the Job to a Redis queue so the API responds instantly
        // In reality, SendBookingConfirmationJob would talk to Twilio/Meta APIs
        SendBookingConfirmationJob::dispatch($confirmation, $messageBody)->onQueue('whatsapp-outbound');

        $this->limitsService->increaseUsage('monthly_confirmation_messages');

        return $confirmation;
    }
}
