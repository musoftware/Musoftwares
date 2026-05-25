<?php

namespace Modules\Booking\app\Features\Widget\Services;

class BookingWidgetLimitsService
{
    public function canUseWidget(int $tenantId): bool
    {
        if (!feature('booking.widget', $tenantId)) {
            return false;
        }

        // Add additional limits checks here (e.g. max_widgets)
        return true;
    }
}
