<?php

namespace Modules\Booking\app\Features\WaConfirm\Services;

use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;

class BookingActionLinkGenerator
{
    protected $tokenManager;

    public function __construct(ConfirmationTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Generates the fully qualified public URL for the customer to click on WhatsApp.
     */
    public function generateLink(BookingWaConfirmation $confirmation, string $actionType): string
    {
        // 1. Generate the secure token
        $token = $this->tokenManager->generateActionToken($confirmation, $actionType);

        // 2. Build the URL (e.g. https://musoftwares.com/api/booking/wa/action/{raw_token})
        // The raw_token was temporarily attached to the model by the TokenManager
        return url("/api/booking/wa/action/{$token->raw_token}");
    }
}
