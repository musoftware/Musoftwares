<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services;

class BookingSmsLimitsService
{
    public function canSendSms(int $tenantId): bool
    {
        if (!feature('booking.sms_notifications', $tenantId)) {
            return false;
        }

        // Add additional limits checks here (e.g. monthly_sms_messages)
        return true;
    }
}
