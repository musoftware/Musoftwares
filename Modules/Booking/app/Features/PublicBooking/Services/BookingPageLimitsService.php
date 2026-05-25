<?php

namespace Modules\Booking\app\Features\PublicBooking\Services;

class BookingPageLimitsService
{
    /**
     * Check if the tenant is allowed to have a public booking page.
     */
    public function canUse(int $tenantId): bool
    {
        // For 'booking-online-page', the limit is purely the feature flag switch in SaaS configs
        // If they don't have the feature unlocked, they can't publish their page.
        
        return feature('booking-online-page');
    }

    /**
     * Optional: Implement page view tracking for limits if required in the future.
     */
    public function getRemainingUsage(int $tenantId): string
    {
        return 'unlimited';
    }
}
