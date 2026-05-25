<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

interface SmsProviderInterface
{
    /**
     * Sends an SMS message.
     * 
     * @param string $mobile The recipient phone number
     * @param string $message The content of the SMS
     * @param array $credentials The decrypted credentials from SmsSetting
     * @return bool True on success, False on failure
     */
    public function send(string $mobile, string $message, array $credentials): bool;
}
