<?php

namespace Modules\Booking\app\Features\WaReminders\Services;

class BookingWhatsAppLimitsService
{
    public function canSend(int $tenantId): bool
    {
        if (!feature('booking.wa_reminders', $tenantId)) {
            return false;
        }

        $remaining = $this->getRemainingUsage($tenantId);
        return $remaining > 0;
    }

    public function increaseUsage(int $tenantId): void
    {
        // E.g., tenant()->incrementUsage('monthly_whatsapp_messages')
    }

    public function getRemainingUsage(int $tenantId): int
    {
        // Fetch from saas config or tenant limits DB
        // Stub implementation
        return 100; 
    }
}
