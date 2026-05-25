<?php

namespace Modules\Booking\app\Features\WaReminders\Services;

interface WhatsAppProviderInterface
{
    /**
     * Send a WhatsApp message to a specific phone number.
     * 
     * @param string $phoneNumber
     * @param string $message
     * @return string The provider's internal message ID for tracking delivery.
     */
    public function sendMessage(string $phoneNumber, string $message): string;

    /**
     * Verify a webhook signature from the provider.
     */
    public function verifyWebhookSignature(array $payload, string $signature): bool;
}
